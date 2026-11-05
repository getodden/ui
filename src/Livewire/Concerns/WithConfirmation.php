<?php

declare(strict_types=1);

namespace Odden\Ui\Livewire\Concerns;

use Illuminate\Support\Str;
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
    protected function confirm(
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
        if ($this->isReserved($action)) {
            throw new InvalidArgumentException("[{$action}] cannot be confirmed.");
        }

        if (! method_exists($this, $action)) {
            throw new InvalidArgumentException("[{$action}] is not a method on ".static::class.'.');
        }

        $declaringClass = (new ReflectionMethod($this, $action))->getDeclaringClass()->getName();

        if (str_starts_with($declaringClass, 'Livewire\\')) {
            throw new InvalidArgumentException("[{$action}] cannot be confirmed.");
        }
    }

    private function isReserved(string $action): bool
    {
        return in_array($action, ['confirm', 'runConfirmation', 'cancelConfirmation', 'render', 'mount', 'boot', 'booted', 'exception', 'rendering', 'rendered', 'scriptSrc'], true)
            || Str::is(['hydrate*', 'dehydrate*', 'updating*', 'updated*'], $action)
            || in_array($action, $this->traitLifecycleHooks(), true);
    }

    /**
     * @return list<string>
     */
    private function traitLifecycleHooks(): array
    {
        $hooks = [];

        foreach (class_uses_recursive($this) as $trait) {
            foreach (['mount', 'boot', 'booted'] as $prefix) {
                $hooks[] = $prefix.class_basename($trait);
            }
        }

        return $hooks;
    }
}
