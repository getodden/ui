@props(['value' => null, 'label' => null])

@php
    throw_if(blank($label), \InvalidArgumentException::class, 'A ui::table.select needs a label.');
    throw_if($value === null || $value === '', \InvalidArgumentException::class, 'A ui::table.select needs a value.');
@endphp

<input
    type="checkbox"
    value="{{ $value }}"
    aria-label="{{ $label }}"
    wire:model.live="selected"
    {{ $attributes->class(['size-4 rounded-control border-gray-400 accent-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:border-gray-500 dark:accent-primary-400 dark:focus-visible:outline-primary-400']) }}
>
