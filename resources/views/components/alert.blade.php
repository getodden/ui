@props(['variant' => 'info', 'heading' => null, 'icon' => null, 'dismissible' => false])

@php
    $role = in_array($variant, ['danger', 'warning'], true) ? 'alert' : 'status';

    $variantClasses = match ($variant) {
        'success' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/40',
        'warning' => 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40',
        'danger' => 'border-rose-200 bg-rose-50 dark:border-rose-900 dark:bg-rose-950/40',
        default => 'border-sky-200 bg-sky-50 dark:border-sky-900 dark:bg-sky-950/40',
    };

    $accentClasses = match ($variant) {
        'success' => 'text-emerald-700 dark:text-emerald-400',
        'warning' => 'text-amber-800 dark:text-amber-300',
        'danger' => 'text-rose-700 dark:text-rose-400',
        default => 'text-sky-700 dark:text-sky-300',
    };

    $defaultIcon = match ($variant) {
        'success' => 'fas-circle-check',
        'warning' => 'fas-triangle-exclamation',
        'danger' => 'fas-circle-xmark',
        default => 'fas-circle-info',
    };

    $hasHeading = filled(trim((string) $heading));
@endphp

<div
    role="{{ $role }}"
    {{ $attributes->merge($dismissible ? ['x-data' => '{ open: true }', 'x-show' => 'open'] : [])->class(["flex items-start gap-3 rounded-surface border p-4 text-gray-700 dark:text-gray-300 {$variantClasses}"]) }}
>
    <x-ui::icon :name="$icon ?? $defaultIcon" size="lg" class="mt-0.5 {{ $accentClasses }}" />

    <div class="min-w-0 flex-1 text-sm">
        @if ($hasHeading)
            <p class="font-semibold {{ $accentClasses }}">{{ $heading }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            x-on:click="open = false"
            aria-label="{{ __('ui::ui.dismiss') }}"
            class="-m-1 rounded-control p-1 text-gray-700 hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:text-gray-300 dark:hover:bg-white/10 dark:focus-visible:outline-primary-400"
        >
            <x-ui::icon name="fas-xmark" />
        </button>
    @endif
</div>
