<?php

declare(strict_types=1);

namespace Odden\Ui\Livewire\Concerns;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use LogicException;
use Odden\Ui\Support\RecordTableState;
use ReflectionMethod;

trait WithRecordTable
{
    use WithPagination;

    private bool $recordsPaginated = false;

    #[Url(except: '')]
    public mixed $search = '';

    #[Url(except: '')]
    public mixed $sort = '';

    #[Url(except: '')]
    public mixed $direction = '';

    #[Url(except: [])]
    public mixed $filters = [];

    #[Url(except: 10)]
    public mixed $perPage = 10;

    #[Url(except: '')]
    public mixed $tab = '';

    public mixed $selected = [];

    /**
     * @return array<string, string|Closure(EloquentBuilder<*>|QueryBuilder, string): void>
     */
    abstract protected function sortableColumns(): array;

    protected function defaultSort(): ?string
    {
        return null;
    }

    protected function defaultDirection(): string
    {
        return 'asc';
    }

    /**
     * @return list<string>
     */
    protected function searchableColumns(): array
    {
        return [];
    }

    /**
     * @return array<string, Closure(EloquentBuilder<*>|QueryBuilder, string): void>
     */
    protected function recordFilters(): array
    {
        return [];
    }

    /**
     * @return array<string, array{label: string, scope: Closure(EloquentBuilder<*>|QueryBuilder): mixed}>
     */
    protected function recordTabs(): array
    {
        return [];
    }

    protected function defaultTab(): ?string
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function bulkActions(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    protected function dangerousBulkActions(): array
    {
        return [];
    }

    protected function maximumSelection(): int
    {
        return max([100, ...$this->perPageOptions()]);
    }

    /**
     * @return list<int>
     */
    protected function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    /**
     * @param  EloquentBuilder<*>|QueryBuilder  $query
     */
    protected function applySearch(EloquentBuilder|QueryBuilder $query, string $term): void
    {
        $columns = $this->searchableColumns();

        if ($columns === []) {
            return;
        }

        $base = $query instanceof EloquentBuilder ? $query->getQuery() : $query;
        $grammar = $base->getGrammar();
        $pattern = '%'.(string) preg_replace('/[!%_]/', '!$0', $term).'%';

        $query->where(function (EloquentBuilder|QueryBuilder $group) use ($columns, $grammar, $pattern): void {
            foreach ($columns as $column) {
                // @phpstan-ignore argument.type
                $group->orWhereRaw('lower('.$grammar->wrap($column).") like lower(?) escape '!'", [$pattern]);
            }
        });
    }

    public function sortBy(string $key): void
    {
        if (! array_key_exists($key, $this->sortableColumns())) {
            return;
        }

        if ($this->activeSort() === $key) {
            $this->direction = $this->activeDirection() === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $key;
            $this->direction = 'asc';
        }

        $this->clearSelection();
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filters = [];
        $this->clearSelection();
        $this->resetPage();
    }

    public function setTab(string $key): void
    {
        $tabs = $this->recordTabs();

        if (! array_key_exists($key, $tabs)) {
            return;
        }

        $this->tab = $key === $this->resolveDefaultTab($tabs) ? '' : $key;
        $this->clearSelection();
        $this->resetPage();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function runBulkAction(string $action): void
    {
        if (! array_key_exists($action, $this->bulkActions()) || ! method_exists($this, $action)) {
            return;
        }

        if ((new ReflectionMethod($this, $action))->isPublic()) {
            throw new LogicException("Bulk action [{$action}] must be protected.");
        }

        $keys = $this->selectedKeys();

        if ($keys === []) {
            return;
        }

        $pendingBefore = $this->pendingConfirmation();

        $this->{$action}($keys);

        if ($this->pendingConfirmation() === $pendingBefore) {
            $this->clearSelection();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pendingConfirmation(): array
    {
        return in_array(WithConfirmation::class, class_uses_recursive($this), true) ? $this->confirmation : [];
    }

    protected function recordTableState(): RecordTableState
    {
        throw_unless($this->recordsPaginated, LogicException::class, 'Call paginateRecords() before recordTableState().');

        $tabs = $this->recordTabs();

        return new RecordTableState(
            array_map(strval(...), array_keys($this->sortableColumns())),
            $this->activeSort(),
            $this->activeDirection(),
            array_map(fn (array $tab): string => $tab['label'], $tabs),
            $this->activeTabIn($tabs),
            count($this->selectedKeys()),
            $this->bulkActions(),
            $this->dangerousBulkActions(),
        );
    }

    protected function activeTab(): ?string
    {
        return $this->activeTabIn($this->recordTabs());
    }

    /**
     * @param  array<string, array{label: string, scope: Closure(EloquentBuilder<*>|QueryBuilder): mixed}>  $tabs
     */
    private function activeTabIn(array $tabs): ?string
    {
        if ($tabs === []) {
            return null;
        }

        $requested = $this->scalarState($this->tab);

        if ($requested !== '' && array_key_exists($requested, $tabs)) {
            return $requested;
        }

        return $this->resolveDefaultTab($tabs);
    }

    /**
     * @param  array<string, array{label: string, scope: Closure(EloquentBuilder<*>|QueryBuilder): mixed}>  $tabs
     */
    private function resolveDefaultTab(array $tabs): ?string
    {
        $default = $this->defaultTab();

        if ($default !== null && array_key_exists($default, $tabs)) {
            return $default;
        }

        $first = array_key_first($tabs);

        return $first === null ? null : (string) $first;
    }

    /**
     * @return list<string>
     */
    protected function selectedKeys(): array
    {
        if (! is_array($this->selected)) {
            return [];
        }

        $keys = [];
        $limit = max(0, $this->maximumSelection());

        foreach ($this->selected as $value) {
            if (count($keys) >= $limit) {
                break;
            }

            $key = $this->scalarState($value);

            if ($key !== '' && mb_strlen($key) <= 255) {
                $keys[$key] = true;
            }
        }

        return array_map(strval(...), array_keys($keys));
    }

    protected function activeSort(): ?string
    {
        $columns = $this->sortableColumns();

        $requested = $this->scalarState($this->sort);

        if ($requested !== '' && array_key_exists($requested, $columns)) {
            return $requested;
        }

        $default = $this->defaultSort();

        return in_array($default, array_map(strval(...), array_keys($columns)), true) ? $default : null;
    }

    /**
     * @return 'asc'|'desc'
     */
    protected function activeDirection(): string
    {
        $requested = strtolower($this->scalarState($this->direction));
        $direction = in_array($requested, ['asc', 'desc'], true) ? $requested : strtolower($this->defaultDirection());

        return $direction === 'desc' ? 'desc' : 'asc';
    }

    /**
     * @return array<string, string>
     */
    protected function activeFilters(): array
    {
        $allowed = $this->recordFilters();
        $active = [];

        if (! is_array($this->filters)) {
            return [];
        }

        foreach ($this->filters as $key => $value) {
            if (! array_key_exists($key, $allowed)) {
                continue;
            }

            $value = $this->scalarState($value);

            if ($value !== '') {
                $active[(string) $key] = $value;
            }
        }

        return $active;
    }

    protected function activePerPage(): int
    {
        $options = $this->perPageOptions();

        $requested = $this->scalarState($this->perPage);

        if (ctype_digit($requested) && in_array((int) $requested, $options, true)) {
            return (int) $requested;
        }

        return $options[0] ?? 10;
    }

    protected function activeSearch(): string
    {
        return mb_substr(trim($this->scalarState($this->search)), 0, 100);
    }

    /**
     * @param  EloquentBuilder<*>|QueryBuilder  $query
     */
    protected function applyRecordTable(EloquentBuilder|QueryBuilder $query): void
    {
        $sort = $this->activeSort();

        if ($sort !== null) {
            $query->reorder();
        }

        $term = $this->activeSearch();

        if ($term !== '') {
            $this->applySearch($query, $term);
        }

        $filters = $this->recordFilters();

        foreach ($this->activeFilters() as $key => $value) {
            $filters[$key]($query, $value);
        }

        $tabs = $this->recordTabs();
        $tab = $this->activeTabIn($tabs);

        if ($tab !== null) {
            $query->where(function (EloquentBuilder|QueryBuilder $group) use ($tabs, $tab): void {
                $tabs[$tab]['scope']($group);
                $this->assertOnlyWheres($group, $tab);
            });
        }

        if ($sort !== null) {
            $this->orderUsing($query, $this->sortableColumns()[$sort], $this->activeDirection());
        }

        $tiebreak = $this->tiebreakColumn($query);

        if ($tiebreak !== null) {
            $query->orderBy($tiebreak);
        }
    }

    /**
     * @param  EloquentBuilder<*>|QueryBuilder  $group
     */
    private function assertOnlyWheres(EloquentBuilder|QueryBuilder $group, string $tab): void
    {
        $base = $group instanceof EloquentBuilder ? $group->getQuery() : $group;

        $addsMore = ($base->joins ?? []) !== []
            || ($base->orders ?? []) !== []
            || ($base->groups ?? []) !== []
            || ($base->havings ?? []) !== []
            || ($base->unions ?? []) !== []
            || $base->columns !== null;

        $removedScopes = $group instanceof EloquentBuilder ? $group->removedScopes() : [];

        throw_if(
            $addsMore || array_diff($removedScopes, [SoftDeletingScope::class]) !== [],
            LogicException::class,
            "Tab [{$tab}] may only add where clauses.",
        );
    }

    /**
     * @param  EloquentBuilder<*>|QueryBuilder  $query
     */
    protected function tiebreakColumn(EloquentBuilder|QueryBuilder $query): ?string
    {
        if (! $query instanceof EloquentBuilder) {
            return null;
        }

        $base = $query->getQuery();

        if (($base->groups ?? []) !== [] || ($base->distinct !== false)) {
            return null;
        }

        return $query->getModel()->getQualifiedKeyName();
    }

    /**
     * @param  EloquentBuilder<*>|QueryBuilder  $query
     * @return LengthAwarePaginator<int, mixed>
     */
    protected function paginateRecords(EloquentBuilder|QueryBuilder $query): LengthAwarePaginator
    {
        $this->applyRecordTable($query);

        $perPage = $this->activePerPage();
        $records = $query->paginate($perPage);

        if ($records->total() > 0 && $records->currentPage() > $records->lastPage()) {
            $this->setPage($records->lastPage());
            $records = $query->paginate($perPage);
        }

        $this->keepSelectionOn($records);
        $this->recordsPaginated = true;

        return $records;
    }

    /**
     * @param  EloquentBuilder<*>  $base
     * @return EloquentBuilder<*>
     */
    protected function selectedRecords(EloquentBuilder $base): EloquentBuilder
    {
        $query = clone $base;

        $this->applyRecordTable($query);

        return $query->whereKey($this->selectedKeys());
    }

    protected function recordKey(mixed $record): mixed
    {
        return $record instanceof Model ? $record->getKey() : data_get($record, 'id');
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $records
     */
    private function keepSelectionOn(LengthAwarePaginator $records): void
    {
        $pageKeys = [];

        foreach ($records->items() as $record) {
            $key = $this->recordKey($record);

            if (! is_int($key) && ! is_string($key)) {
                return;
            }

            $pageKeys[] = (string) $key;
        }

        $this->selected = array_values(array_intersect($this->selectedKeys(), $pageKeys));
    }

    /**
     * @param  EloquentBuilder<*>|QueryBuilder  $query
     * @param  'asc'|'desc'  $direction
     */
    private function orderUsing(EloquentBuilder|QueryBuilder $query, mixed $column, string $direction): void
    {
        if ($column instanceof Closure) {
            $column($query, $direction);
        } elseif (is_string($column)) {
            $query->orderBy($column, $direction);
        }
    }

    private function scalarState(mixed $value): string
    {
        return is_string($value) ? mb_scrub($value) : (is_int($value) || is_float($value) ? (string) $value : '');
    }

    private function scrubbedState(mixed $value): mixed
    {
        if (is_string($value)) {
            return mb_scrub($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        $scrubbed = [];

        foreach ($value as $key => $item) {
            $scrubbed[is_string($key) ? mb_scrub($key) : $key] = $this->scrubbedState($item);
        }

        return $scrubbed;
    }

    public function dehydrateWithRecordTable(): void
    {
        $this->search = $this->scrubbedState($this->search);
        $this->sort = $this->scrubbedState($this->sort);
        $this->direction = $this->scrubbedState($this->direction);
        $this->filters = $this->scrubbedState($this->filters);
        $this->perPage = $this->scrubbedState($this->perPage);
        $this->tab = $this->scrubbedState($this->tab);
        $this->selected = $this->selectedKeys();
    }

    public function updatedWithRecordTable(string $name): void
    {
        $property = explode('.', $name)[0];

        if ($property === 'paginators') {
            $this->clearSelection();

            return;
        }

        if (in_array($property, ['search', 'sort', 'direction', 'filters', 'perPage', 'tab'], true)) {
            $this->clearSelection();
            $this->resetPage();
        }
    }
}
