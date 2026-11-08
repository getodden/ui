<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRecordTable;

final class OwnConfirmingFlagRecordTableComponent extends Component
{
    use WithRecordTable;

    public bool $confirming = true;

    /**
     * @var list<string>
     */
    public array $received = [];

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return ['name' => 'name'];
    }

    /**
     * @return array<string, string>
     */
    protected function bulkActions(): array
    {
        return ['recordSelection' => 'Record'];
    }

    /**
     * @param  list<string>  $keys
     */
    protected function recordSelection(array $keys): void
    {
        $this->received = $keys;
    }

    public function updatedPaginators(): void
    {
        $this->received = ['scrolled'];
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
