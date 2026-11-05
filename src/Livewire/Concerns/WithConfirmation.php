<?php

declare(strict_types=1);

namespace Odden\Ui\Livewire\Concerns;

use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Odden\Ui\Enums\ButtonVariant;
use Odden\Ui\Support\PendingConfirmation;
use ReflectionMethod;

trait WithConfirmation
{
    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $confirmation = [];

    public bool $confirming = false;

    /**
     * @param  array<int|string, mixed>  $arguments
     */
    public function confirm(
        string $action,
        array $arguments,
        string $title,
        string $message,
        string $confirmLabel,
        ButtonVariant $variant = ButtonVariant::Danger,
    ): void {
        $this->assertConfirmable($action);

        $this->confirmation = (new PendingConfirmation($action, $arguments, $title, $message, $confirmLabel, $variant))->toArray();
        $this->confirming = true;
    }

    public function runConfirmation(): void
    {
        if ($this->confirmation === []) {
            return;
        }

        $pending = PendingConfirmation::fromArray($this->confirmation);

        $this->cancelConfirmation();

        $this->assertConfirmable($pending->action);
        $this->{$pending->action}(...$pending->arguments);
    }

    public function cancelConfirmation(): void
    {
        $this->confirmation = [];
        $this->confirming = false;
    }

    public function updatedConfirming(bool $open): void
    {
        if (! $open) {
            $this->cancelConfirmation();
        }
    }

    private function assertConfirmable(string $action): void
    {
        if (! method_exists($this, $action)) {
            throw new InvalidArgumentException("[{$action}] is not a method on ".static::class.'.');
        }

        $declaringClass = (new ReflectionMethod($this, $action))->getDeclaringClass()->getName();

        if (str_starts_with($declaringClass, 'Livewire\\')
            || in_array($action, ['confirm', 'runConfirmation', 'cancelConfirmation', 'updatedConfirming'], true)) {
            throw new InvalidArgumentException("[{$action}] cannot be confirmed.");
        }
    }
}
