<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

final readonly class FieldState
{
    public function __construct(
        public ?string $name,
        public string $id,
        public ?string $error,
        public ?string $describedBy,
    ) {}

    public static function resolve(
        ComponentAttributeBag $attributes,
        ?string $label,
        ?string $name,
        ?string $id,
        ?string $error,
        bool $hasHint,
        mixed $errors,
    ): self {
        if (blank($label) && ! $attributes->has('aria-label') && ! $attributes->has('aria-labelledby')) {
            throw new InvalidArgumentException('A ui field needs a label, an aria-label or an aria-labelledby.');
        }

        $wireModel = $attributes->whereStartsWith('wire:model')->first();
        $wireModel = is_string($wireModel) && $wireModel !== '' ? $wireModel : null;

        $name = filled($name) ? $name : $wireModel;
        $id = filled($id) ? $id : self::idFromName($name);
        $error = filled($error) ? $error : self::errorFor($errors, $wireModel ?? $name);

        $describedBy = array_filter([
            $hasHint ? $id.'-hint' : null,
            $error !== null ? $id.'-error' : null,
            $attributes->get('aria-describedby'),
        ]);

        return new self($name, $id, $error, $describedBy === [] ? null : implode(' ', $describedBy));
    }

    public function controlAttributes(ComponentAttributeBag $attributes, bool $required): ComponentAttributeBag
    {
        return $attributes->except('aria-describedby')->merge(array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'aria-describedby' => $this->describedBy,
            'aria-invalid' => $this->error === null ? null : 'true',
            'required' => $required ? true : null,
        ], fn (string|bool|null $value): bool => $value !== null));
    }

    public static function idFromName(?string $name): string
    {
        if ($name === null) {
            return 'ui-field-'.Str::lower(Str::random(8));
        }

        $slug = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $name), '-');

        return 'ui-'.($slug === '' ? Str::lower(Str::random(8)) : $slug);
    }

    private static function errorFor(mixed $errors, ?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $bag = $errors instanceof ViewErrorBag ? $errors->getBag('default') : $errors;

        if (! $bag instanceof MessageBag) {
            return null;
        }

        $key = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
        $message = $bag->first($key);

        return $message === '' ? null : $message;
    }
}
