<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

use Illuminate\Support\Collection;

final readonly class BoardSlice
{
    /**
     * @param  Collection<int, mixed>  $records
     */
    public function __construct(
        public Collection $records,
        public bool $hasMore,
    ) {}
}
