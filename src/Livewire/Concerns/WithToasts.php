<?php

declare(strict_types=1);

namespace Odden\Ui\Livewire\Concerns;

use Odden\Ui\Enums\ToastVariant;
use Odden\Ui\Support\Toasts;

trait WithToasts
{
    public function toast(string $message, ToastVariant $variant = ToastVariant::Success): void
    {
        $this->dispatch('ui-toast', message: $message, variant: $variant->value);
    }

    public function toastAfterRedirect(string $message, ToastVariant $variant = ToastVariant::Success): void
    {
        Toasts::flash($message, $variant);
    }
}
