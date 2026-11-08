@aware(['id'])
@props(['name' => null])

@php
    throw_if(blank($name), \InvalidArgumentException::class, 'A ui::tab needs a name.');

    $suffix = substr(sha1((string) $name), 0, 10);
@endphp

<div
    role="tabpanel"
    id="{{ $id }}-panel-{{ $suffix }}"
    aria-labelledby="{{ $id }}-tab-{{ $suffix }}"
    tabindex="0"
    x-show="selected === @js((string) $name)"
    style="display: none"
    {{ $attributes->class(['pt-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:focus-visible:outline-primary-400']) }}
>
    {{ $slot }}
</div>
