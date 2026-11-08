<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRecordTable;
use Workbench\App\Models\Fruit;

final class NameKeyedRecordTableComponent extends Component
{
    use WithRecordTable;

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return ['name' => 'name'];
    }

    protected function recordKey(mixed $record): mixed
    {
        return $record->name;
    }

    /**
     * @return array<string, string>
     */
    protected function bulkActions(): array
    {
        return ['noop' => 'Noop'];
    }

    public function report(): string
    {
        return json_encode($this->selectedKeys(), JSON_THROW_ON_ERROR);
    }

    public function stateWithoutRecords(): string
    {
        return json_encode($this->recordTableState(), JSON_THROW_ON_ERROR);
    }

    public function render(): string
    {
        $this->paginateRecords(Fruit::query());

        return '<div></div>';
    }
}
