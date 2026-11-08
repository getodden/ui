@aware(['columns' => [], 'column' => null, 'movable' => true, 'reorderable' => true])
@props(['id', 'label', 'locked' => false])

@php
    $id = (string) $id;
    $current = (string) $column;
    $canMove = $movable && ! $locked;
    $disabledClasses = 'aria-disabled:cursor-not-allowed aria-disabled:opacity-50';
@endphp

<li
    data-board-card="{{ $id }}"
    data-board-label="{{ $label }}"
    draggable="{{ $canMove ? 'true' : 'false' }}"
    x-bind:class="dragging === @js($id) && 'opacity-50'"
    {{ $attributes->merge(['wire:key' => 'ui-board-card-'.$id])->class(['rounded-control border border-gray-200 bg-white p-3 text-sm text-gray-900 shadow-xs dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100', 'cursor-grab' => $canMove]) }}
>
    <div>{{ $slot }}</div>

    @if ($canMove)
        <div class="mt-2 flex items-center gap-1">
            <select
                data-board-move
                aria-label="{{ __('ui::ui.board.move_card', ['card' => $label]) }}"
                x-bind:aria-disabled="pending ? 'true' : null"
                class="block min-w-0 flex-1 rounded-control border border-gray-400 bg-white px-2 py-1 text-xs text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 {{ $disabledClasses }} dark:border-gray-500 dark:bg-gray-900 dark:text-gray-100 dark:focus-visible:outline-primary-400"
            >
                <option value="" selected>{{ __('ui::ui.board.move_to') }}</option>
                @foreach ($columns as $key => $columnLabel)
                    @continue((string) $key === $current)
                    <option value="{{ $key }}">{{ $columnLabel }}</option>
                @endforeach
            </select>
            <x-ui::button type="button" variant="outline" size="sm" data-board-confirm aria-label="{{ __('ui::ui.board.move_confirm', ['card' => $label]) }}" x-bind:aria-disabled="pending ? 'true' : null" class="{{ $disabledClasses }}">{{ __('ui::ui.board.move') }}</x-ui::button>
            @if ($reorderable)
                <x-ui::button type="button" variant="outline" size="sm" data-board-shift="-1" aria-label="{{ __('ui::ui.board.move_up', ['card' => $label]) }}" x-bind:aria-disabled="pending ? 'true' : null" class="{{ $disabledClasses }}"><span aria-hidden="true">&uarr;</span></x-ui::button>
                <x-ui::button type="button" variant="outline" size="sm" data-board-shift="1" aria-label="{{ __('ui::ui.board.move_down', ['card' => $label]) }}" x-bind:aria-disabled="pending ? 'true' : null" class="{{ $disabledClasses }}"><span aria-hidden="true">&darr;</span></x-ui::button>
            @endif
        </div>
    @endif
</li>
