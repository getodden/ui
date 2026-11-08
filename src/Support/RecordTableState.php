<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

final readonly class RecordTableState
{
    /**
     * @param  list<string>  $sortable
     * @param  array<string, string>  $tabs
     * @param  array<string, string>  $bulkActions
     * @param  list<string>  $dangerousBulkActions
     */
    public function __construct(
        public array $sortable,
        public ?string $sort,
        public string $direction,
        public array $tabs = [],
        public ?string $tab = null,
        public int $selectedCount = 0,
        public array $bulkActions = [],
        public array $dangerousBulkActions = [],
    ) {}

    public function isDangerousBulkAction(string $method): bool
    {
        return in_array($method, $this->dangerousBulkActions, true);
    }

    public function isActiveTab(string $key): bool
    {
        return $this->tab !== null && $this->tab === $key;
    }

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
