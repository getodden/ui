@aware(['id', 'tabs', 'selected'])
@props(['name' => null])

@php
    throw_if(blank($name), \InvalidArgumentException::class, 'A ui::tab needs a name.');

    throw_if($attributes->has('id'), \InvalidArgumentException::class, 'A ui::tab takes its id from the tabs.');

    $suffix = substr(sha1((string) $name), 0, 10);
    $keys = array_map('strval', array_keys($tabs ?? []));
    $initial = in_array((string) $selected, $keys, true) ? (string) $selected : ($keys[0] ?? '');
    $isInitial = (string) $name === $initial;
@endphp

<div
    role="tabpanel"
    id="{{ $id }}-panel-{{ $suffix }}"
    aria-labelledby="{{ $id }}-tab-{{ $suffix }}"
    tabindex="0"
    x-show="selected === @js((string) $name)"
    @unless ($isInitial) style="display: none" @endunless
    {{ $attributes->class(['pt-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:focus-visible:outline-primary-400']) }}
>
    {{ $slot }}
</div>
