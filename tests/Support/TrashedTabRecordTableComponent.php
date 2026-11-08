<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRecordTable;
use Workbench\App\Models\SoftFruit;

final class TrashedTabRecordTableComponent extends Component
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
            'archived' => ['label' => 'Archived', 'scope' => fn (Builder|QueryBuilder $query) => $query->onlyTrashed()],
        ];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->paginateRecords(SoftFruit::query())->pluck('name')->all();
    }

    /**
     * @return list<string>
     */
    public function selectedNames(): array
    {
        return $this->selectedRecords(SoftFruit::query())->orderBy('name')->pluck('name')->all();
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
