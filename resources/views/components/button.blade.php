@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
    'icon' => null,
    'label' => null,
])

@php
    $variant = $variant instanceof \BackedEnum ? $variant->value : $variant;
    $iconOnly = $slot->isEmpty();

    throw_if($iconOnly && blank($label), \InvalidArgumentException::class, 'An icon-only ui::button needs a label.');

    $sizeClasses = match ($size) {
        'sm' => $iconOnly ? 'p-1.5 text-sm' : 'px-3 py-1.5 text-sm',
        'lg' => $iconOnly ? 'p-2.5 text-base' : 'px-5 py-2.5 text-base',
        default => $iconOnly ? 'p-2 text-sm' : 'px-4 py-2 text-sm',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700',
        'outline' => 'border border-gray-300 text-gray-900 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-800',
        'ghost' => 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
        'danger' => 'bg-rose-700 text-white hover:bg-rose-800 dark:bg-rose-400 dark:text-gray-950 dark:hover:bg-rose-300',
        default => 'bg-primary-700 text-white hover:bg-primary-800 dark:bg-primary-400 dark:text-gray-950 dark:hover:bg-primary-300',
    };

    $classes = "inline-flex items-center justify-center gap-2 rounded-control font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:focus-visible:outline-primary-400 disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:cursor-not-allowed aria-disabled:opacity-50 {$sizeClasses} {$variantClasses}";

    $tag = $href === null ? 'button' : 'a';
    $loadingTarget = $attributes->get('wire:target') ?? $attributes->get('wire:click');

    $elementAttributes = $attributes->merge(array_filter([
        'type' => $tag === 'button' ? $type : null,
        'disabled' => $tag === 'button' && $disabled ? true : null,
        'href' => $tag === 'a' && ! $disabled ? $href : null,
        'role' => $tag === 'a' && $disabled ? 'link' : null,
        'tabindex' => $tag === 'a' && $disabled ? '-1' : null,
        'aria-disabled' => $tag === 'a' && $disabled ? 'true' : null,
        'aria-label' => $iconOnly ? $label : null,
        'wire:target' => $loadingTarget,
        'wire:loading.attr' => $loadingTarget ? 'aria-busy' : null,
    ], fn ($value) => $value !== null))->class($classes);
@endphp

<{{ $tag }} {{ $elementAttributes }}>
    <x-ui::icon :name="$icon" />
    {{ $slot }}
    @if ($loadingTarget)
        <x-ui::icon name="fas-spinner" class="hidden animate-spin" wire:loading.class.remove="hidden" :wire:target="$loadingTarget" />
    @endif
</{{ $tag }}>
