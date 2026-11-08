<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

final class ChartData
{
    public const int MAXIMUM_CATEGORIES = 366;

    public const int MAXIMUM_SERIES = 12;

    public static function number(mixed $value): ?float
    {
        $number = match (true) {
            is_int($value), is_float($value) => (float) $value,
            is_string($value) && is_numeric($value) => (float) $value,
            default => null,
        };

        return $number !== null && is_finite($number) ? $number : null;
    }

    public static function text(mixed $value): string
    {
        return is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
    }

    /**
     * @return list<string>
     */
    public static function categories(mixed $categories): array
    {
        if (! is_array($categories)) {
            return [];
        }

        return array_map(self::text(...), array_slice(array_values($categories), 0, self::MAXIMUM_CATEGORIES));
    }

    /**
     * @return list<array{name: string, values: list<float|null>}>
     */
    public static function series(mixed $series, int $categoryCount): array
    {
        if (! is_array($series) || $categoryCount === 0) {
            return [];
        }

        $series = array_slice(array_values(array_filter($series, is_array(...))), 0, self::MAXIMUM_SERIES);
        $normalised = [];

        foreach ($series as $index => $entry) {
            $values = is_array($entry['values'] ?? null) ? array_values($entry['values']) : [];
            $name = trim(self::text($entry['name'] ?? ''));

            $normalised[] = [
                'name' => $name !== '' ? $name : (count($series) > 1 ? __('ui::ui.chart.series', ['number' => $index + 1]) : __('ui::ui.chart.value')),
                'values' => array_map(fn (int $position): ?float => self::number($values[$position] ?? null), range(0, $categoryCount - 1)),
            ];
        }

        return $normalised;
    }

    /**
     * @param  list<array{name: string, values: list<float|null>}>  $series
     */
    public static function hasValues(array $series): bool
    {
        foreach ($series as $entry) {
            foreach ($entry['values'] as $value) {
                if ($value !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $categories
     * @param  list<array{name: string, values: list<float|null>}>  $series
     * @return list<list<string>>
     */
    public static function tableRows(array $categories, array $series): array
    {
        return array_map(
            fn (string $category, int $position): array => [
                $category,
                ...array_map(fn (array $entry): string => $entry['values'][$position] === null ? '' : ChartScale::format($entry['values'][$position]), $series),
            ],
            $categories,
            array_keys($categories),
        );
    }
}
