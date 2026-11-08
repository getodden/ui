@props(['column', 'label', 'count' => 0, 'total' => null, 'hasMore' => false, 'headingLevel' => 3])

@php
    $column = (string) $column;
    $level = min(max((int) $headingLevel, 2), 6);
    $headingId = 'ui-board-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
@endphp

<section
    aria-labelledby="{{ $headingId }}"
    data-board-column
    data-board-label="{{ $label }}"
    {{ $attributes->class(['flex w-72 shrink-0 flex-col rounded-surface border border-gray-200 bg-gray-50 text-gray-900 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100']) }}
>
    <header class="flex items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
        <h{{ $level }} id="{{ $headingId }}" data-board-heading tabindex="-1" class="min-w-0 truncate text-sm font-semibold">{{ $label }}</h{{ $level }}>
        <div class="flex items-center gap-2">
            @if (filled($total))
                <span class="text-xs text-gray-500 dark:text-gray-400" data-board-total>{{ $total }}</span>
            @endif
            <x-ui::badge><span class="sr-only">{{ __('ui::ui.board.count') }}</span><span data-board-count>{{ (int) $count }}</span></x-ui::badge>
        </div>
    </header>

    <ul
        role="list"
        data-board-list="{{ $column }}"
        @if ($hasMore) data-board-more @endif
        x-bind:class="over === @js($column) && 'bg-primary-50 dark:bg-primary-950'"
        class="peer flex min-h-16 flex-col gap-2 rounded-b-surface p-2"
    >{{ $slot }}</ul>

    <p class="block px-4 pb-4 text-center text-sm text-gray-500 peer-has-[[data-board-card]]:hidden dark:text-gray-400">{{ __('ui::ui.board.empty') }}</p>

    @if ($hasMore)
        <div class="border-t border-gray-200 p-2 dark:border-gray-800">
            <x-ui::button variant="ghost" size="sm" class="w-full" wire:click="loadMoreCards({{ \Illuminate\Support\Js::from($column) }})">{{ __('ui::ui.board.show_more') }}<span class="sr-only"> {{ __('ui::ui.board.show_more_in', ['column' => $label]) }}</span></x-ui::button>
        </div>
    @endif
</section>
