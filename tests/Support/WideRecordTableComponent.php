<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRecordTable;

class WideRecordTableComponent extends Component
{
    use WithRecordTable;

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
     * @return list<int>
     */
    protected function perPageOptions(): array
    {
        return [5, 250];
    }

    /**
     * @return array<string, string>
     */
    protected function bulkActions(): array
    {
        return ['exposedSelection' => 'Exposed'];
    }

    /**
     * @param  list<string>  $keys
     */
    public function exposedSelection(array $keys): void
    {
        $this->received = $keys;
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
