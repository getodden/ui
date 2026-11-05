<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

use Odden\Ui\Enums\ButtonVariant;

final readonly class PendingConfirmation
{
    /**
     * @param  array<int|string, mixed>  $arguments
     */
    public function __construct(
        public string $action,
        public array $arguments,
        public string $title,
        public string $message,
        public string $confirmLabel,
        public ButtonVariant $variant,
    ) {}

    /**
     * @param  array<string, mixed>  $state
     */
    public static function fromArray(array $state): self
    {
        return new self(
            action: (string) $state['action'],
            arguments: (array) $state['arguments'],
            title: (string) $state['title'],
            message: (string) $state['message'],
            confirmLabel: (string) $state['confirmLabel'],
            variant: ButtonVariant::from((string) $state['variant']),
        );
    }

    /**
     * @return array{action: string, arguments: array<int|string, mixed>, title: string, message: string, confirmLabel: string, variant: string}
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'arguments' => $this->arguments,
            'title' => $this->title,
            'message' => $this->message,
            'confirmLabel' => $this->confirmLabel,
            'variant' => $this->variant->value,
        ];
    }
}
