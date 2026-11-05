<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Livewire\Component;

final class EditorComponent extends Component
{
    public string $body = '';

    public function render(): string
    {
        return '<div><x-ui::editor label="Body" wire:model="body" /></div>';
    }
}
