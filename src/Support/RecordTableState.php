<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

final readonly class RecordTableState
{
    /**
     * @param  list<string>  $sortable
     */
    public function __construct(
        public array $sortable,
        public ?string $sort,
        public string $direction,
    ) {}

    public function isSortable(string $key): bool
    {
        return in_array($key, $this->sortable, true);
    }

    public function isSortedBy(string $key): bool
    {
        return $this->sort !== null && $this->sort === $key;
    }

    public function ariaSort(string $key): ?string
    {
        if (! $this->isSortedBy($key)) {
            return null;
        }

        return $this->direction === 'desc' ? 'descending' : 'ascending';
    }
}
