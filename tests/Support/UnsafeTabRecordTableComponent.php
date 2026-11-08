<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRecordTable;
use Workbench\App\Models\Fruit;

final class UnsafeTabRecordTableComponent extends Component
{
    use WithRecordTable;

    public ?Closure $scope = null;

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
            'newest' => ['label' => 'Newest', 'scope' => $this->scope ?? fn (Builder|QueryBuilder $query) => $query->latest('id')],
        ];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->paginateRecords(Fruit::query())->pluck('name')->all();
    }

    public function render(): string
    {
        $this->paginateRecords(Fruit::query());

        return '<div></div>';
    }
}
