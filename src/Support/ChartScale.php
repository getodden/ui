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

    public static function compact(float $value): string
    {
        $absolute = abs($value);

        foreach ([1_000_000_000 => 'B', 1_000_000 => 'M', 1_000 => 'K'] as $threshold => $suffix) {
            if ($absolute >= $threshold) {
                return self::format(round($value / $threshold, 1)).$suffix;
            }
        }

        return self::format($value);
    }

    public static function coordinate(float $value, float $minimum, float $maximum, float $from, float $to): float
    {
        if ($maximum <= $minimum) {
            return round(($from + $to) / 2, 2);
        }

        return round($from + ($value - $minimum) / ($maximum - $minimum) * ($to - $from), 2);
    }
}
