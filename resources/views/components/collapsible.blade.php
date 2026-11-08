@props(['id' => null, 'heading' => null, 'description' => null, 'level' => 2, 'open' => false])

@php
    throw_if(blank($heading), \InvalidArgumentException::class, 'A ui::collapsible needs a heading.');

    $baseId = filled($id) ? $id : 'ui-collapsible-'.substr(sha1($heading.'|'.$attributes->get('wire:key')), 0, 10);
    $headingTag = 'h'.min(6, max(1, (int) $level));
@endphp

<section
    @if (filled($id)) id="{{ $id }}" @endif
    x-data="{ open: @js((bool) $open) }"
    x-modelable="open"
    {{ $attributes->class(['rounded-surface border border-gray-200 bg-white text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100']) }}
>
    <{{ $headingTag }} class="text-base font-semibold">
        <button
            type="button"
            id="{{ $baseId }}-trigger"
            aria-controls="{{ $baseId }}-panel"
            aria-expanded="{{ $open ? 'true' : 'false' }}"
            x-bind:aria-expanded="open ? 'true' : 'false'"
            x-on:click="open = !open"
            class="flex w-full items-center justify-between gap-3 rounded-surface px-5 py-4 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:focus-visible:outline-primary-400"
        >
            <span>
                {{ $heading }}
                @if (filled($description))
                    <span class="block text-sm font-normal text-gray-500 dark:text-gray-400">{{ $description }}</span>
                @endif
            </span>
            <x-ui::icon name="fas-chevron-down" size="sm" class="text-gray-500 transition-transform dark:text-gray-400" x-bind:class="open ? 'rotate-180' : ''" />
        </button>
    </{{ $headingTag }}>

    <div
        id="{{ $baseId }}-panel"
        role="region"
        aria-labelledby="{{ $baseId }}-trigger"
        x-show="open"
        @unless ($open) style="display: none" @endunless
        class="border-t border-gray-200 p-5 dark:border-gray-800"
    >
        {{ $slot }}
    </div>
</section>
