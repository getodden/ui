@props(['label' => null, 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'required' => false])

@php
    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $label, $name, $id, $error, filled($hint), $errors ?? null);
    $controlAttributes = $field->controlAttributes($attributes, $required)->class(['size-4 shrink-0 cursor-pointer rounded-control accent-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:accent-primary-400 dark:focus-visible:outline-primary-400']);
@endphp

<x-ui::field :id="$field->id" :label="$label" :hint="$hint" :error="$field->error" :required="$required" inline>
    <input type="checkbox" {{ $controlAttributes }}>
</x-ui::field>
