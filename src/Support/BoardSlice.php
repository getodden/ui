<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

use Illuminate\Support\Collection;

/**
 * @template TRecord
 */
final readonly class BoardSlice
{
    public const int END_POSITION = 100000;

    /**
     * @param  Collection<int, TRecord>  $records
     */
    public function __construct(
        public Collection $records,
        public bool $hasMore,
    ) {}
}
