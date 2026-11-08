<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRecordTable;
use Workbench\App\Models\Fruit;

final class ScopeStrippingTabRecordTableComponent extends Component
{
    use WithRecordTable;

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return ['name' => 'name'];
    }

    /**
     * @return array<string, array{label: string, scope: \Closure(Builder<*>|QueryBuilder): mixed}>
     */
    protected function recordTabs(): array
    {
        return [
            'all' => ['label' => 'All', 'scope' => fn (Builder|QueryBuilder $query) => $query->withoutGlobalScope('guard')->where('price', '>', 0)],
        ];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        $query = Fruit::query()->withGlobalScope('guard', fn (Builder $builder) => $builder->where('colour', 'red'));

        return $this->paginateRecords($query)->pluck('name')->all();
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
