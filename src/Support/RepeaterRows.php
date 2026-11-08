<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Odden\Ui\Enums\RepeaterDirection;

final class RepeaterRows
{
    public const KEY = '_key';

    /**
     * @param  array<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    public static function onlyRows(array $rows): array
    {
        return array_values(array_filter($rows, is_array(...)));
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    public static function withoutKeys(array $rows): array
    {
        return array_map(function (mixed $row): array {
            if (! is_array($row)) {
                throw new InvalidArgumentException('A repeater row must be an array.');
            }

            unset($row[self::KEY]);

            return $row;
        }, array_values($rows));
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    public static function withKeys(array $rows): array
    {
        $seen = [];
        $keyed = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('A repeater row must be an array.');
            }

            $key = $row[self::KEY] ?? null;

            if (! is_string($key) || $key === '' || isset($seen[$key])) {
                $key = self::newKey();
            }

            $seen[$key] = true;
            $keyed[] = [...$row, self::KEY => $key];
        }

        return $keyed;
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    public static function add(array $rows, RepeaterSpec $spec): array
    {
        $rows = self::withKeys($rows);

        if ($spec->max !== null && count($rows) >= $spec->max) {
            return $rows;
        }

        return [...$rows, [...$spec->blank, self::KEY => self::newKey()]];
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    public static function remove(array $rows, RepeaterSpec $spec, string $key): array
    {
        $rows = self::withKeys($rows);

        if (count($rows) <= $spec->min) {
            return $rows;
        }

        return array_values(array_filter($rows, fn (array $row): bool => $row[self::KEY] !== $key));
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    public static function move(array $rows, string $key, RepeaterDirection $direction): array
    {
        $rows = self::withKeys($rows);
        $from = self::positionOf($rows, $key);

        if ($from === null) {
            return $rows;
        }

        $to = $direction === RepeaterDirection::Up ? $from - 1 : $from + 1;

        if ($to < 0 || $to >= count($rows)) {
            return $rows;
        }

        [$rows[$from], $rows[$to]] = [$rows[$to], $rows[$from]];

        return $rows;
    }

    /**
     * @param  array<mixed>  $rows
     * @param  array<mixed>  $keys
     * @return list<array<string, mixed>>
     */
    public static function reorder(array $rows, array $keys): array
    {
        $rows = self::withKeys($rows);
        $current = array_column($rows, self::KEY);

        if (! array_is_list($keys) || count(array_filter($keys, is_string(...))) !== count($keys) || count($keys) !== count($current) || array_diff($keys, $current) !== [] || count(array_unique($keys)) !== count($keys)) {
            return $rows;
        }

        $byKey = array_column($rows, null, self::KEY);

        return array_map(fn (string $key): array => $byKey[$key], $keys);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private static function positionOf(array $rows, string $key): ?int
    {
        foreach ($rows as $position => $row) {
            if ($row[self::KEY] === $key) {
                return $position;
            }
        }

        return null;
    }

    private static function newKey(): string
    {
        return Str::lower(Str::random(12));
    }
}
