<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithConfirmation;
use Odden\Ui\Livewire\Concerns\WithRecordTable;
use Workbench\App\Models\Fruit;

class BulkRecordTableComponent extends Component
{
    use WithConfirmation;
    use WithRecordTable;

    /**
     * @var list<string>
     */
    public array $received = [];

    public int $maximum = 100;

    public int $tabReads = 0;

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return ['name' => 'name'];
    }

    /**
     * @return list<string>
     */
    protected function searchableColumns(): array
    {
        return ['name'];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /**
     * @return list<string>
     */
    protected function dangerousBulkActions(): array
    {
        return ['askToRemove'];
    }

    protected function maximumSelection(): int
    {
        return $this->maximum;
    }

    /**
     * @return array<string, array{label: string, scope: \Closure(Builder<*>|QueryBuilder): mixed}>
     */
    protected function recordTabs(): array
    {
        $this->tabReads++;

        return [
            'all' => ['label' => 'All', 'scope' => fn (Builder|QueryBuilder $query) => $query],
            'red' => ['label' => 'Red', 'scope' => fn (Builder|QueryBuilder $query) => $query->where('colour', 'red')],
            'loose' => ['label' => 'Loose', 'scope' => fn (Builder|QueryBuilder $query) => $query->where('colour', 'red')->orWhere('price', '>=', 500)],
            'pricey' => ['label' => 'Pricey', 'scope' => fn (Builder|QueryBuilder $query) => $query->where('price', '>=', 500)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function bulkActions(): array
    {
        return ['recordSelection' => 'Record', 'refuseSelection' => 'Refuse', 'askToRemove' => 'Remove'];
    }

    /**
     * @param  list<string>  $keys
     */
    protected function recordSelection(array $keys): void
    {
        $this->received = $keys;
    }

    /**
     * @param  list<string>  $keys
     */
    protected function refuseSelection(array $keys): void
    {
        throw new InvalidArgumentException('refused');
    }

    /**
     * @param  list<string>  $keys
     */
    protected function askToRemove(array $keys): void
    {
        $this->confirm('removeSelection', [$keys], 'Remove?', 'Sure?', 'Remove');
    }

    /**
     * @param  list<string>  $keys
     */
    protected function removeSelection(array $keys): void
    {
        $this->received = $keys;
        $this->clearSelection();
    }

    public function unlistedAction(): void
    {
        $this->received = ['unlisted'];
    }

    public function report(): string
    {
        return json_encode([
            'tab' => $this->activeTab(),
            'selected' => $this->selectedKeys(),
            'state' => $this->recordTableState(),
        ], JSON_THROW_ON_ERROR);
    }

    public function render(): string
    {
        return Blade::render(<<<'BLADE'
        <div>
            <x-ui::table caption="Fruit" :state="$state" :records="$records" :columns="['name' => 'Name']" :selectable-keys="$records->pluck('id')->all()" row-actions>
                @foreach ($records as $fruit)
                    <tr wire:key="fruit-{{ $fruit->id }}">
                        <td><x-ui::table.select :value="$fruit->id" :label="'Select '.$fruit->name" /></td>
                        <th scope="row">{{ $fruit->name }}</th>
                        <td>
                            <x-ui::table.actions :label="'Actions for '.$fruit->name">
                                <x-ui::table.action wire:click="unlistedAction">Open</x-ui::table.action>
                                <x-ui::table.action href="/fruit/{{ $fruit->id }}">Show</x-ui::table.action>
                                <x-ui::table.action danger>Remove</x-ui::table.action>
                            </x-ui::table.actions>
                        </td>
                    </tr>
                @endforeach
            </x-ui::table>
        </div>
        BLADE, ['records' => $this->paginateRecords(Fruit::query()), 'state' => $this->recordTableState()]);
    }
}
