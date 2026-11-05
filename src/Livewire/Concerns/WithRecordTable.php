<?php

declare(strict_types=1);

namespace Odden\Ui\Livewire\Concerns;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Odden\Ui\Support\RecordTableState;

trait WithRecordTable
{
    use WithPagination;

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

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filters = [];
        $this->resetPage();
    }

    protected function recordTableState(): RecordTableState
    {
        return new RecordTableState(
            array_map(strval(...), array_keys($this->sortableColumns())),
            $this->activeSort(),
            $this->activeDirection(),
        );
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

        if ($sort !== null) {
            $this->orderUsing($query, $this->sortableColumns()[$sort], $this->activeDirection());
        }

        if ($query instanceof EloquentBuilder) {
            $query->orderBy($query->getModel()->getQualifiedKeyName());
        }
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

        return $records;
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
        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : '';
    }

    public function updatedWithRecordTable(string $name): void
    {
        if (in_array(explode('.', $name)[0], ['search', 'sort', 'direction', 'filters', 'perPage'], true)) {
            $this->resetPage();
        }
    }
}
