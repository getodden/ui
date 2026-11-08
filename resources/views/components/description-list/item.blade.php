@props(['label' => null])

@php
    throw_if(blank($label), \InvalidArgumentException::class, 'A ui::description-list.item needs a label.');

    $isEmpty = blank(trim((string) $slot));
@endphp

<div {{ $attributes->class(['grid gap-1 py-3 sm:grid-cols-3 sm:gap-4']) }}>
    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
    <dd class="text-sm text-gray-900 sm:col-span-2 dark:text-gray-100">
        @if ($isEmpty)
            <span aria-hidden="true">&mdash;</span>
            <span class="sr-only">{{ __('ui::ui.not_set') }}</span>
        @else
            {{ $slot }}
        @endif
    </dd>
</div>
