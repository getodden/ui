@aware(['columns' => [], 'column' => null])
@props(['id', 'label'])

@php
    $id = (string) $id;
    $current = (string) $column;
@endphp

<li
    data-board-card="{{ $id }}"
    data-board-label="{{ $label }}"
    draggable="true"
    x-bind:class="dragging === @js($id) && 'opacity-50'"
    {{ $attributes->class(['cursor-grab rounded-control border border-gray-200 bg-white p-3 text-sm text-gray-900 shadow-xs dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100']) }}
>
    <div>{{ $slot }}</div>

    <div class="mt-2 flex items-center gap-1">
        <select
            data-board-move
            aria-label="{{ __('ui::ui.board.move_card', ['card' => $label]) }}"
            x-bind:disabled="pending"
            class="block min-w-0 flex-1 rounded-control border border-gray-400 bg-white px-2 py-1 text-xs text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-500 dark:bg-gray-900 dark:text-gray-100 dark:focus-visible:outline-primary-400"
        >
            <option value="" selected>{{ __('ui::ui.board.move_to') }}</option>
            @foreach ($columns as $key => $columnLabel)
                @continue((string) $key === $current)
                <option value="{{ $key }}">{{ $columnLabel }}</option>
            @endforeach
        </select>
        <x-ui::button type="button" variant="outline" size="sm" data-board-shift="-1" aria-label="{{ __('ui::ui.board.move_up', ['card' => $label]) }}" x-bind:disabled="pending"><span aria-hidden="true">&uarr;</span></x-ui::button>
        <x-ui::button type="button" variant="outline" size="sm" data-board-shift="1" aria-label="{{ __('ui::ui.board.move_down', ['card' => $label]) }}" x-bind:disabled="pending"><span aria-hidden="true">&darr;</span></x-ui::button>
    </div>
</li>
