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
        $name = $this->assertConfirmable($action);

        $this->confirmation = (new PendingConfirmation($name, $arguments, $title, $message, $confirmLabel, $variant))->toArray();
        $this->confirming = true;
    }

    public function runConfirmation(): void
    {
        if ($this->confirmation === []) {
            return;
        }

        $pending = PendingConfirmation::fromArray($this->confirmation);

        $this->cancelConfirmation();

        $name = $this->assertConfirmable($pending->action);
        $this->{$name}(...$pending->arguments);
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

    private function assertConfirmable(string $action): string
    {
        $method = method_exists($this, $action) ? new ReflectionMethod($this, $action) : null;
        $name = $method?->getName() ?? $action;

        if ($this->isReserved($name) || str_starts_with($method?->getDeclaringClass()->getName() ?? '', 'Livewire\\')) {
            throw new InvalidArgumentException("[{$name}] cannot be confirmed.");
        }

        if ($method === null) {
            throw new InvalidArgumentException("[{$action}] is not a method on ".static::class.'.');
        }

        return $name;
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
