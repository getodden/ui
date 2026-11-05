@props(['label' => null, 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'required' => false])

@php
    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $name, $id, $error, filled($hint), $errors ?? null);
    $controlAttributes = $attributes->merge(array_filter([
        'id' => $field->id,
        'name' => $field->name,
        'aria-describedby' => $field->describedBy,
        'aria-invalid' => $field->error === null ? null : 'true',
        'required' => $required ? true : null,
    ], fn ($value) => $value !== null))->class(['peer absolute inset-0 size-full cursor-pointer opacity-0 disabled:cursor-not-allowed']);
@endphp

<x-ui::field :id="$field->id" :label="$label" :hint="$hint" :error="$field->error" :required="$required" inline>
    <span class="relative inline-flex h-6 w-11 shrink-0">
        <input type="checkbox" role="switch" {{ $controlAttributes }}>
        <span aria-hidden="true" class="pointer-events-none absolute inset-0 rounded-full bg-gray-500 transition-colors peer-checked:bg-primary-700 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary-700 peer-disabled:opacity-50 dark:peer-checked:bg-primary-400 dark:peer-focus-visible:outline-primary-400"></span>
        <span aria-hidden="true" class="pointer-events-none absolute left-0.5 top-0.5 size-5 rounded-full bg-white transition-transform peer-checked:translate-x-5 dark:bg-gray-950"></span>
    </span>
</x-ui::field>
