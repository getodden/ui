<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRecordTable;
use Workbench\App\Models\Fruit;

final class NameKeyOnlyRecordTableComponent extends Component
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
     * @return list<string>
     */
    public function selectedNames(): array
    {
        return $this->selectedRecords(Fruit::query())->orderBy('name')->pluck('name')->all();
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
