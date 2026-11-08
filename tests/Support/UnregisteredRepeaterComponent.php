<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRepeater;

final class UnregisteredRepeaterComponent extends Component
{
    use WithRepeater;

    /**
     * @var list<array<string, mixed>>
     */
    public array $items = [['name' => 'First']];

    /**
     * @return array<string, never>
     */
    protected function repeaters(): array
    {
        return [];
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>
            <x-ui::repeater label="People" name="items" :rows="$items">
                @foreach ($items as $index => $item)
                    <x-ui::repeater.row :index="$index">x</x-ui::repeater.row>
                @endforeach
            </x-ui::repeater>
        </div>
        BLADE;
    }
}
