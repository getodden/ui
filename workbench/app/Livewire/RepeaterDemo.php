<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithRepeater;
use Odden\Ui\Support\RepeaterSpec;

final class RepeaterDemo extends Component
{
    use WithRepeater;

    /**
     * @var list<array<string, mixed>>
     */
    public array $phones = [
        ['label' => 'Office', 'number' => '555 0100'],
        ['label' => 'Mobile', 'number' => '555 0101'],
        ['label' => 'Home', 'number' => '555 0102'],
    ];

    public bool $saved = false;

    /**
     * @return array<string, RepeaterSpec>
     */
    protected function repeaters(): array
    {
        return ['phones' => new RepeaterSpec(min: 1, max: 5, blank: ['label' => '', 'number' => ''])];
    }

    public function save(): void
    {
        $this->saved = false;

        $this->validate([
            'phones' => ['required', 'array', 'min:1'],
            'phones.*.label' => ['required', 'string', 'max:40'],
            'phones.*.number' => ['required', 'string', 'max:40'],
        ]);

        $this->saved = true;
    }

    public function render(): View
    {
        return view('workbench::livewire.repeater-demo');
    }
}
