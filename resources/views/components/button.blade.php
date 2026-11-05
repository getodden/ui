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
        'outline' => 'border border-gray-400 text-gray-900 hover:bg-gray-50 dark:border-gray-500 dark:text-gray-100 dark:hover:bg-gray-800',
        'ghost' => 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
        'danger' => 'bg-rose-700 text-white hover:bg-rose-800 dark:bg-rose-400 dark:text-gray-950 dark:hover:bg-rose-300',
        default => 'bg-primary-700 text-white hover:bg-primary-800 dark:bg-primary-400 dark:text-gray-950 dark:hover:bg-primary-300',
    };

    $classes = "group/ui-button inline-flex items-center justify-center gap-2 rounded-control font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:focus-visible:outline-primary-400 disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:cursor-not-allowed aria-disabled:opacity-50 {$sizeClasses} {$variantClasses}";

    $tag = $href === null ? 'button' : 'a';
    $disabledLink = $tag === 'a' && $disabled;

    if ($disabledLink) {
        $attributes = $attributes->filter(
            fn ($value, $key): bool => preg_match('/^(wire:(click|navigate|keydown|keyup)|x-on:(click|keydown|keyup|keypress)|@(click|keydown|keyup|keypress)|x-bind(:href)?$|:href$|href$)/', (string) $key) !== 1
        );
    }

    $trackLoading = $attributes->whereStartsWith('wire:click')->isNotEmpty();
    $busyObserver = "const button = \$el.parentElement; (button.uiBusyObserver ??= new MutationObserver(() => button.hasAttribute('data-loading') ? button.setAttribute('aria-busy', 'true') : button.removeAttribute('aria-busy'))).observe(button, { attributes: true, attributeFilter: ['data-loading'] })";

    $elementAttributes = $attributes->merge(array_filter([
        'type' => $tag === 'button' ? $type : null,
        'disabled' => $tag === 'button' && $disabled ? true : null,
        'href' => $tag === 'a' && ! $disabled ? $href : null,
        'role' => $disabledLink ? 'link' : null,
        'tabindex' => $disabledLink ? '-1' : null,
        'aria-disabled' => $disabledLink ? 'true' : null,
        'aria-label' => $iconOnly ? $label : null,
    ], fn ($value) => $value !== null))->class($classes);
@endphp

<{{ $tag }} {{ $elementAttributes }}>
    <x-ui::icon :name="$icon" />
    {{ $slot }}
    @if ($trackLoading)
        <x-ui::icon name="fas-spinner" class="hidden animate-spin group-data-loading/ui-button:inline-block" :x-init="$busyObserver" />
    @endif
</{{ $tag }}>
