<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRepeater;
use Odden\Ui\Support\RepeaterSpec;

final class RepeaterComponent extends Component
{
    use WithRepeater;

    /**
     * @var list<array<string, mixed>>
     */
    public array $items = [['name' => 'First'], ['name' => 'Second'], ['name' => 'Third']];

    public string $untouched = 'keep';

    /**
     * @return array<string, RepeaterSpec>
     */
    protected function repeaters(): array
    {
        return ['items' => new RepeaterSpec(min: 1, max: 4, blank: ['name' => ''])];
    }

    public function save(): void
    {
        $this->validate(['items.*.name' => 'required|min:2'], attributes: ['items.*.name' => 'name']);
    }

    public function saveStrictly(): void
    {
        $this->validate(['items.*' => 'array:name,_key', 'items.*.name' => 'required']);
    }

    public function saveWithoutKeyAllowance(): void
    {
        $this->validate(['items.*' => 'array:name']);
    }

    public function loadFromStorage(): void
    {
        $this->items = [['name' => 'From storage']];
    }

    public function loadOverMaximum(): void
    {
        $this->items = array_map(fn (int $number): array => ['name' => 'Stored '.$number], range(1, 6));
        $this->items[2]['name'] = '';
    }

    public function resetItems(): void
    {
        $this->reset('items');
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>
            <x-ui::repeater label="People" name="items" :rows="$items" item-label="Person">
                @foreach ($items as $index => $item)
                    <x-ui::repeater.row :index="$index">
                        <x-ui::input label="Name" wire:model="items.{{ $index }}.name" />
                    </x-ui::repeater.row>
                @endforeach
            </x-ui::repeater>
        </div>
        BLADE;
    }
}
