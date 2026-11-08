@props(['danger' => false, 'disabled' => false])

<button
    type="button"
    role="menuitem"
    tabindex="-1"
    @disabled($disabled)
    {{ $attributes->class([
        'block w-full px-4 py-2 text-start focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:outline-primary-400',
        'hover:bg-gray-100 dark:hover:bg-gray-800' => ! $danger,
        'text-rose-700 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-gray-800' => $danger,
    ]) }}
>{{ $slot }}</button>
