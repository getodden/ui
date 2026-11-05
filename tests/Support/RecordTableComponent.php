<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRecordTable;
use Workbench\App\Models\Fruit;

class RecordTableComponent extends Component
{
    use WithRecordTable;

    public int $searchUpdates = 0;

    /**
     * @return array<string, string|\Closure(Builder<*>|QueryBuilder, string): void>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'price' => 'price',
            'colourPlain' => 'colour',
            'colour' => function (Builder|QueryBuilder $query, string $direction): void {
                $query->orderBy('colour', $direction)->orderBy('name', 'desc');
            },
        ];
    }

    protected function defaultSort(): ?string
    {
        return 'name';
    }

    protected function searchableColumns(): array
    {
        return ['name', 'colour'];
    }

    protected function recordFilters(): array
    {
        return [
            'colour' => function (Builder|QueryBuilder $query, string $value): void {
                $query->where('colour', $value);
            },
        ];
    }

    public function orderedSql(): string
    {
        $query = Fruit::query()->orderByDesc('price');
        $this->applyRecordTable($query);

        return $query->toRawSql();
    }

    public function updatedSearch(): void
    {
        $this->searchUpdates++;
    }

    public function report(): string
    {
        return json_encode([
            'sort' => $this->activeSort(),
            'direction' => $this->activeDirection(),
            'filters' => $this->activeFilters(),
            'perPage' => $this->activePerPage(),
            'search' => $this->activeSearch(),
            'state' => $this->recordTableState(),
        ], JSON_THROW_ON_ERROR);
    }

    public function render(): string
    {
        return Blade::render(<<<'BLADE'
        <div>
            <ul>
                @foreach ($records as $fruit)
                    <li>{{ $fruit->name }}</li>
                @endforeach
            </ul>
            <p>total={{ $records->total() }} page={{ $records->currentPage() }} per={{ $records->perPage() }}</p>
        </div>
        BLADE, ['records' => $this->paginateRecords(Fruit::query())]);
    }
}
