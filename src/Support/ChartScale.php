<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

final class ChartScale
{
    /**
     * @return non-empty-list<float>
     */
    public static function ticks(float $minimum, float $maximum, int $target = 5): array
    {
        $scale = max(abs($minimum), abs($maximum));

        if ($maximum - $minimum <= $scale * 1e-9) {
            $maximum = $minimum + max(1.0, $scale * 1e-6);
        }

        $range = $maximum - $minimum;

        $rough = $range / max(1, $target);
        $magnitude = 10.0 ** (int) floor(log10($rough));
        $normalised = $rough / $magnitude;
        $step = match (true) {
            $normalised <= 1 => 1,
            $normalised <= 2 => 2,
            $normalised <= 5 => 5,
            default => 10,
        } * $magnitude;

        $first = floor($minimum / $step + 1e-9);
        $last = ceil($maximum / $step - 1e-9);

        if ($last <= $first) {
            $last = $first + 1;
        }

        $precision = max(0, 2 - (int) floor(log10($step)));
        $ticks = [round($first * $step, $precision)];

        for ($offset = 1; $offset <= (int) ($last - $first); $offset++) {
            $ticks[] = round(($first + $offset) * $step, $precision);
        }

        return $ticks;
    }

    public static function format(float $value): string
    {
        $absolute = abs($value);
        $decimals = $absolute > 0 && $absolute < 1 ? min(10, 2 - (int) floor(log10($absolute))) : 2;
        $formatted = number_format($value, $decimals, '.', ',');
        $trimmed = rtrim(rtrim($formatted, '0'), '.');

        return in_array($trimmed, ['-0', ''], true) ? '0' : $trimmed;
    }

    public static function compact(float $value, int $decimals = 1): string
    {
        $units = [1_000_000_000 => 'B', 1_000_000 => 'M', 1_000 => 'K'];
        $absolute = abs($value);

        foreach ($units as $threshold => $suffix) {
            if ($absolute < $threshold) {
                continue;
            }

            $scaled = round($value / $threshold, $decimals);

            if (abs($scaled) >= 1000 && $threshold < 1_000_000_000) {
                return self::compact($scaled * $threshold, $decimals);
            }

            return self::format($scaled).$suffix;
        }

        return self::format($value);
    }

    /**
     * @param  list<float>  $values
     * @return list<string>
     */
    public static function compactAll(array $values): array
    {
        foreach (range(1, 6) as $decimals) {
            $labels = array_map(fn (float $value): string => self::compact($value, $decimals), $values);

            if (count(array_unique($labels)) === count(array_unique($values))) {
                return $labels;
            }
        }

        return array_map(fn (float $value): string => self::format($value), $values);
    }

    public static function coordinate(float $value, float $minimum, float $maximum, float $from, float $to): float
    {
        if ($maximum <= $minimum) {
            return round(($from + $to) / 2, 2);
        }

        return round($from + ($value - $minimum) / ($maximum - $minimum) * ($to - $from), 2);
    }
}
