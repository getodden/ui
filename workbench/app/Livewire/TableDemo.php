<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRecordTable;
use Workbench\App\Models\Fruit;

final class TableDemo extends Component
{
    use WithRecordTable;

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return ['name' => 'name', 'price' => 'price'];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /**
     * @return list<string>
     */
    protected function searchableColumns(): array
    {
        return ['name'];
    }

    /**
     * @return array<string, Closure(Builder<*>|QueryBuilder, string): void>
     */
    protected function recordFilters(): array
    {
        return [
            'colour' => function (Builder|QueryBuilder $query, string $value): void {
                $query->where('colour', $value);
            },
        ];
    }

    /**
     * @return list<int>
     */
    protected function perPageOptions(): array
    {
        return [10, 25];
    }

    public int $unrelated = 0;

    public string $lastAction = '';

    /**
     * @return array<string, array{label: string, scope: Closure(Builder<*>|QueryBuilder): mixed}>
     */
    protected function recordTabs(): array
    {
        return [
            'all' => ['label' => 'All', 'scope' => fn (Builder|QueryBuilder $query) => $query],
            'purple' => ['label' => 'Purple', 'scope' => fn (Builder|QueryBuilder $query) => $query->where('colour', 'purple')],
            'pricey' => ['label' => 'Pricey', 'scope' => fn (Builder|QueryBuilder $query) => $query->where('price', '>=', 500)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function bulkActions(): array
    {
        return ['describeSelected' => 'Describe selected'];
    }

    /**
     * @param  list<string>  $keys
     */
    protected function describeSelected(array $keys): void
    {
        $this->lastAction = 'Selected: '.Fruit::query()->whereKey($keys)->orderBy('name')->pluck('name')->implode(', ');
    }

    public function inspect(int $id): void
    {
        $this->lastAction = 'Inspected: '.Fruit::query()->whereKey($id)->value('name');
    }

    public function unrelatedAction(): void
    {
        usleep(600000);
        $this->unrelated++;
    }

    public function updatedPerPage(): void
    {
        usleep(600000);
    }

    public function render(): View
    {
        return view('workbench::livewire.table-demo', [
            'records' => $this->paginateRecords(Fruit::query()),
            'colours' => Fruit::query()->distinct()->orderBy('colour')->pluck('colour', 'colour')->all(),
        ]);
    }
}
