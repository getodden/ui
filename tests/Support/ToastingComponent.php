<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Livewire\Component;
use Odden\Ui\Enums\ToastVariant;
use Odden\Ui\Livewire\Concerns\WithToasts;

final class ToastingComponent extends Component
{
    use WithToasts;

    public function inform(): void
    {
        $this->toast('Saved');
    }

    public function fail(): void
    {
        $this->toast('Could not save', ToastVariant::Danger);
    }

    public function saveAndLeave(): void
    {
        $this->toastAfterRedirect('Saved', ToastVariant::Info);
        $this->toastAfterRedirect('And more', ToastVariant::Success);
        $this->redirect('/elsewhere');
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
