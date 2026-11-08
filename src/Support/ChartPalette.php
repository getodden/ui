<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

final class ChartPalette
{
    public const int SIZE = 6;

    private const array FALLBACKS = ['#6d28d9', '#0f766e', '#b45309', '#be123c', '#0369a1', '#4d7c0f'];

    public static function size(): int
    {
        return self::SIZE;
    }

    public static function paint(int $index): string
    {
        $slot = $index % self::size();

        return 'var(--ui-chart-'.($slot + 1).', '.self::FALLBACKS[$slot].')';
    }
}
