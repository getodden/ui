<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

final class ChartGeometry
{
    private const float WIDTH = 480.0;

    private const float VERTICAL_HEIGHT = 240.0;

    private const float HORIZONTAL_BAND = 14.0;

    private const int LABEL_LENGTH = 14;

    /**
     * @param  list<string>  $categories
     * @param  list<array{name: string, values: list<float|null>}>  $series
     * @return array{width: float, height: float, horizontal: bool, baseline: float, plot: array{left: float, right: float, top: float, bottom: float}, ticks: list<array{position: float, label: string}>, labels: list<array{position: float, text: string, full: string}>, bars: list<array{x: float, y: float, width: float, height: float, series: int, title: string}>}
     */
    public static function bars(array $categories, array $series, bool $horizontal, bool $stacked): array
    {
        $count = count($categories);
        $seriesCount = count($series);
        $intervals = self::intervals($series, $count, $stacked);

        $lowest = 0.0;
        $highest = 0.0;

        foreach ($intervals as $row) {
            foreach ($row as $interval) {
                if ($interval !== null) {
                    $lowest = min($lowest, $interval[0]);
                    $highest = max($highest, $interval[1]);
                }
            }
        }

        $ticks = ChartScale::ticks($lowest, $highest);
        $domainMinimum = $ticks[0];
        $domainMaximum = $ticks[array_key_last($ticks)];

        $bandSize = $horizontal ? max(24.0, ($stacked ? 1 : $seriesCount) * self::HORIZONTAL_BAND + 10.0) : 0.0;
        $height = $horizontal ? 8.0 + 24.0 + $count * $bandSize : self::VERTICAL_HEIGHT;
        $plot = $horizontal
            ? ['left' => 96.0, 'right' => self::WIDTH - 16.0, 'top' => 8.0, 'bottom' => $height - 24.0]
            : ['left' => 48.0, 'right' => self::WIDTH - 8.0, 'top' => 8.0, 'bottom' => $height - 28.0];

        $valueAxis = fn (float $value): float => $horizontal
            ? ChartScale::coordinate($value, $domainMinimum, $domainMaximum, $plot['left'], $plot['right'])
            : ChartScale::coordinate($value, $domainMinimum, $domainMaximum, $plot['bottom'], $plot['top']);

        $bandLength = $horizontal ? ($plot['bottom'] - $plot['top']) / $count : ($plot['right'] - $plot['left']) / $count;
        $groups = $stacked ? 1 : $seriesCount;
        $usable = $bandLength * 0.7;
        $gap = min(2.0, $usable * 0.1 / max(1, $groups - 1));
        $thickness = ($usable - $gap * ($groups - 1)) / $groups;

        $bars = [];

        foreach ($intervals as $position => $row) {
            $bandStart = ($horizontal ? $plot['top'] : $plot['left']) + $position * $bandLength + ($bandLength - $usable) / 2;

            foreach ($row as $seriesIndex => $interval) {
                if ($interval === null) {
                    continue;
                }

                $offset = $bandStart + ($stacked ? 0 : $seriesIndex) * ($thickness + $gap);
                $lowEnd = $valueAxis($interval[0]);
                $highEnd = $valueAxis($interval[1]);
                $length = max(1.0, round(abs($highEnd - $lowEnd), 2));
                $growth = $horizontal ? 1.0 : -1.0;
                $start = round(
                    $series[$seriesIndex]['values'][$position] >= 0
                        ? min($lowEnd, $lowEnd + $growth * $length)
                        : min($highEnd, $highEnd - $growth * $length),
                    2,
                );
                $title = $categories[$position].', '.$series[$seriesIndex]['name'].': '.ChartScale::format((float) $series[$seriesIndex]['values'][$position]);

                $bars[] = $horizontal
                    ? ['x' => $start, 'y' => round($offset, 2), 'width' => $length, 'height' => round($thickness, 2), 'series' => $seriesIndex, 'title' => $title]
                    : ['x' => round($offset, 2), 'y' => $start, 'width' => round($thickness, 2), 'height' => $length, 'series' => $seriesIndex, 'title' => $title];
            }
        }

        $step = $horizontal ? 1 : self::labelStep($count, $plot['right'] - $plot['left'], 48.0);
        $labels = [];

        foreach ($categories as $position => $category) {
            if ($position % $step === 0) {
                $labels[] = [
                    'position' => round(($horizontal ? $plot['top'] : $plot['left']) + ($position + 0.5) * $bandLength, 2),
                    'text' => self::shorten($category),
                    'full' => $category,
                ];
            }
        }

        return [
            'width' => self::WIDTH,
            'height' => $height,
            'horizontal' => $horizontal,
            'baseline' => $valueAxis(0.0),
            'plot' => $plot,
            'ticks' => array_map(fn (float $tick): array => ['position' => $valueAxis($tick), 'label' => ChartScale::compact($tick)], $ticks),
            'labels' => $labels,
            'bars' => $bars,
        ];
    }

    /**
     * @param  list<string>  $categories
     * @param  list<array{name: string, values: list<float|null>}>  $series
     * @return array{width: float, height: float, plot: array{left: float, right: float, top: float, bottom: float}, ticks: list<array{position: float, label: string}>, labels: list<array{position: float, text: string, full: string}>, lines: list<array{series: int, path: string|null, points: list<array{x: float, y: float, marker: bool, title: string}>}>}
     */
    public static function lines(array $categories, array $series): array
    {
        $count = count($categories);
        $plot = ['left' => 48.0, 'right' => self::WIDTH - 12.0, 'top' => 12.0, 'bottom' => self::VERTICAL_HEIGHT - 28.0];

        $present = [];

        foreach ($series as $entry) {
            foreach ($entry['values'] as $value) {
                if ($value !== null) {
                    $present[] = $value;
                }
            }
        }

        throw_if($present === [], \InvalidArgumentException::class, 'A line chart needs at least one value.');

        $lowest = min($present);
        $highest = max($present);

        if ($lowest === $highest) {
            $lowest = min($lowest, 0.0);
            $highest = max($highest, 0.0);
        }

        $ticks = ChartScale::ticks($lowest, $highest);
        $domainMinimum = $ticks[0];
        $domainMaximum = $ticks[array_key_last($ticks)];

        $horizontalAt = fn (int $position): float => $count === 1
            ? round(($plot['left'] + $plot['right']) / 2, 2)
            : ChartScale::coordinate($position, 0, $count - 1, $plot['left'], $plot['right']);
        $verticalAt = fn (float $value): float => ChartScale::coordinate($value, $domainMinimum, $domainMaximum, $plot['bottom'], $plot['top']);

        $lines = [];

        foreach ($series as $seriesIndex => $entry) {
            $coordinates = array_map(
                fn (?float $value, int $position): ?array => $value === null ? null : ['x' => $horizontalAt($position), 'y' => $verticalAt($value)],
                $entry['values'],
                array_keys($entry['values']),
            );

            $points = [];
            $path = [];

            foreach (self::runs($coordinates) as $run) {
                if (count($run) > 1) {
                    $path[] = self::pathFor($run);
                }

                foreach ($run as $position => $coordinate) {
                    $points[] = [
                        'x' => $coordinate['x'],
                        'y' => $coordinate['y'],
                        'marker' => count($run) === 1 || $count <= 40,
                        'title' => $categories[$position].', '.$entry['name'].': '.ChartScale::format((float) $entry['values'][$position]),
                    ];
                }
            }

            $lines[] = ['series' => $seriesIndex, 'path' => $path === [] ? null : implode(' ', $path), 'points' => $points];
        }

        $step = self::labelStep($count, $plot['right'] - $plot['left'], 48.0);
        $labels = [];

        foreach ($categories as $position => $category) {
            if ($position % $step === 0) {
                $labels[] = ['position' => $horizontalAt($position), 'text' => self::shorten($category), 'full' => $category];
            }
        }

        return [
            'width' => self::WIDTH,
            'height' => self::VERTICAL_HEIGHT,
            'plot' => $plot,
            'ticks' => array_map(fn (float $tick): array => ['position' => $verticalAt($tick), 'label' => ChartScale::compact($tick)], $ticks),
            'labels' => $labels,
            'lines' => $lines,
        ];
    }

    /**
     * @param  list<float|null>  $values
     * @return array{width: float, height: float, path: string|null, dots: list<array{x: float, y: float}>}
     */
    public static function sparkline(array $values): array
    {
        $width = 96.0;
        $height = 28.0;
        $padding = 3.0;

        $present = array_values(array_filter($values, fn (?float $value): bool => $value !== null));
        throw_if($present === [], \InvalidArgumentException::class, 'A sparkline needs at least one value.');

        $lowest = min($present);
        $highest = max($present);
        $count = count($values);

        $coordinates = array_map(
            fn (?float $value, int $position): ?array => $value === null ? null : [
                'x' => $count === 1 ? round($width / 2, 2) : ChartScale::coordinate($position, 0, $count - 1, $padding, $width - $padding),
                'y' => ChartScale::coordinate($value, $lowest, $highest, $height - $padding, $padding),
            ],
            $values,
            array_keys($values),
        );

        $runs = self::runs($coordinates);
        $dots = [];
        $path = [];

        foreach ($runs as $run) {
            if (count($run) > 1) {
                $path[] = self::pathFor($run);
            } else {
                $dots[] = array_values($run)[0];
            }
        }

        $lastRun = end($runs);
        $last = $lastRun === false ? null : array_values($lastRun)[count($lastRun) - 1];

        if ($last !== null && ! in_array($last, $dots, true)) {
            $dots[] = $last;
        }

        return ['width' => $width, 'height' => $height, 'path' => $path === [] ? null : implode(' ', $path), 'dots' => $dots];
    }

    /**
     * @param  list<array{label: string, value: float}>  $segments
     * @return array{total: float, segments: list<array{path: string, series: int, label: string, value: float, share: string}>}
     */
    public static function donut(array $segments): array
    {
        $total = array_sum(array_column($segments, 'value'));
        $centre = 60.0;
        $outer = 56.0;
        $inner = 34.0;
        $start = -90.0;
        $shapes = [];

        foreach ($segments as $index => $segment) {
            $sweep = $segment['value'] / $total * 360;
            $whole = $sweep >= 359.99;
            $end = $start + $sweep;

            $path = $whole
                ? self::circle($centre, $outer).' '.self::circle($centre, $inner)
                : self::arc($centre, $outer, $inner, $start, $end);

            $shapes[] = [
                'path' => $path,
                'series' => $index,
                'label' => $segment['label'],
                'value' => $segment['value'],
                'share' => ChartScale::format(round($segment['value'] / $total * 100, 1)).'%',
            ];

            $start = $end;
        }

        return ['total' => $total, 'segments' => $shapes];
    }

    /**
     * @param  list<array{name: string, values: list<float|null>}>  $series
     * @return list<list<array{0: float, 1: float}|null>>
     */
    private static function intervals(array $series, int $count, bool $stacked): array
    {
        $rows = [];

        for ($position = 0; $position < $count; $position++) {
            $positive = 0.0;
            $negative = 0.0;
            $row = [];

            foreach ($series as $entry) {
                $value = $entry['values'][$position];

                $row[] = match (true) {
                    $value === null => null,
                    ! $stacked => $value >= 0 ? [0.0, $value] : [$value, 0.0],
                    $value >= 0 => [$positive, $positive += $value],
                    default => [$negative += $value, $negative - $value],
                };
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  list<array{x: float, y: float}|null>  $coordinates
     * @return list<array<int, array{x: float, y: float}>>
     */
    private static function runs(array $coordinates): array
    {
        $runs = [];
        $current = [];

        foreach ($coordinates as $position => $coordinate) {
            if ($coordinate === null) {
                if ($current !== []) {
                    $runs[] = $current;
                    $current = [];
                }

                continue;
            }

            $current[$position] = $coordinate;
        }

        if ($current !== []) {
            $runs[] = $current;
        }

        return $runs;
    }

    /**
     * @param  array<int, array{x: float, y: float}>  $run
     */
    private static function pathFor(array $run): string
    {
        $commands = [];

        foreach (array_values($run) as $index => $coordinate) {
            $commands[] = ($index === 0 ? 'M' : 'L').$coordinate['x'].' '.$coordinate['y'];
        }

        return implode(' ', $commands);
    }

    private static function labelStep(int $count, float $length, float $minimumSpacing): int
    {
        return (int) max(1, ceil($count / max(1, floor($length / $minimumSpacing))));
    }

    private static function shorten(string $text): string
    {
        return mb_strimwidth($text, 0, self::LABEL_LENGTH, '…');
    }

    private static function circle(float $centre, float $radius): string
    {
        $left = $centre - $radius;
        $diameter = $radius * 2;

        return "M{$left} {$centre} a{$radius} {$radius} 0 1 0 {$diameter} 0 a{$radius} {$radius} 0 1 0 -{$diameter} 0Z";
    }

    private static function arc(float $centre, float $outer, float $inner, float $start, float $end): string
    {
        $large = $end - $start > 180 ? 1 : 0;
        $point = fn (float $radius, float $angle): string => round($centre + $radius * cos(deg2rad($angle)), 2).' '.round($centre + $radius * sin(deg2rad($angle)), 2);

        return 'M'.$point($outer, $start)
            ."A{$outer} {$outer} 0 {$large} 1 ".$point($outer, $end)
            .'L'.$point($inner, $end)
            ."A{$inner} {$inner} 0 {$large} 0 ".$point($inner, $start).'Z';
    }
}
