@props(['name' => null, 'size' => 'md'])

@php
    $sizeClasses = match ($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };
@endphp

@if ($name instanceof \Illuminate\View\ComponentSlot)
    {{ $name }}
@elseif (filled($name))
    {{ svg($name, trim($sizeClasses.' shrink-0 '.$attributes->get('class')), [...$attributes->except('class')->getAttributes(), 'aria-hidden' => 'true', 'focusable' => 'false']) }}
@endif
