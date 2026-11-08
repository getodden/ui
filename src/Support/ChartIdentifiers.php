<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

final class ChartIdentifiers
{
    /**
     * @var array<string, int>
     */
    private array $issued = [];

    public function next(string $kind, string $label): string
    {
        $base = 'ui-chart-'.substr(hash('xxh3', $kind.'|'.$label), 0, 8);
        $count = $this->issued[$base] = ($this->issued[$base] ?? 0) + 1;

        return $count === 1 ? $base : $base.'-'.$count;
    }
}
