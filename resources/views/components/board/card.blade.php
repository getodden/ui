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

    <select
        data-board-move
        aria-label="{{ __('ui::ui.board.move_card', ['card' => $label]) }}"
        x-bind:disabled="pending"
        class="mt-2 block w-full rounded-control border border-gray-400 bg-white px-2 py-1 text-xs text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-500 dark:bg-gray-900 dark:text-gray-100 dark:focus-visible:outline-primary-400"
    >
        <option value="" selected>{{ __('ui::ui.board.move_to') }}</option>
        @foreach ($columns as $key => $columnLabel)
            @continue((string) $key === $current)
            <option value="{{ $key }}">{{ $columnLabel }}</option>
        @endforeach
    </select>
</li>
