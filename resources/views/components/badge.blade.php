@props(['variant' => 'gray', 'icon' => null])

@php
    $variantClasses = match ($variant) {
        'primary' => 'bg-primary-50 text-primary-700 dark:bg-primary-950 dark:text-primary-400',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
        'warning' => 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        'danger' => 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-400',
        'info' => 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium {$variantClasses}"]) }}>
    <x-ui::icon :name="$icon" size="sm" />
    {{ $slot }}
</span>
