@props(['href' => null, 'icon' => null, 'variant' => 'default', 'disabled' => false])

@php
    $safeHref = filled($href) ? \Odden\Ui\Support\SafeUri::clean((string) $href) : null;
    $tag = $safeHref === null || $disabled ? 'button' : 'a';
    $variantClasses = $variant === 'danger'
        ? 'text-rose-700 hover:bg-rose-50 focus-visible:bg-rose-50 dark:text-rose-400 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800'
        : 'text-gray-900 hover:bg-gray-100 focus-visible:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800';
    $itemAttributes = $attributes->merge(array_filter([
        'type' => $tag === 'button' ? 'button' : null,
        'href' => $tag === 'a' ? $safeHref : null,
        'disabled' => $tag === 'button' && $disabled ? true : null,
    ]))->class(["flex w-full items-center gap-2 px-3 py-2 text-left text-sm focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary-700 dark:focus-visible:outline-primary-400 disabled:cursor-not-allowed disabled:opacity-50", $variantClasses]);
@endphp

<{{ $tag }} role="menuitem" tabindex="-1" {{ $itemAttributes }}>
    <x-ui::icon :name="$icon" size="sm" />
    {{ $slot }}
</{{ $tag }}>
