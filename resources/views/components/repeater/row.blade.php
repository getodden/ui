@props(['index'])

@aware(['name', 'rows' => [], 'itemLabel' => null])

@php
    $rows = array_values($rows);
    $key = $rows[$index][\Odden\Ui\Support\RepeaterRows::KEY] ?? null;

    throw_unless(is_string($key) && $key !== '', \InvalidArgumentException::class, 'A ui::repeater.row needs a row with a key; use the WithRepeater trait or Odden\Ui\Support\RepeaterRows::withKeys().');

    $itemLabel = filled($itemLabel) ? $itemLabel : __('ui::ui.repeater.item');
    $title = $itemLabel.' '.($index + 1);
    $titleId = 'ui-'.trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $name), '-').'-row-'.$index.'-title';
    $keyScript = \Illuminate\Support\Js::from($key);
    $canRemove = count($rows) > ((isset($this) && method_exists($this, 'repeaterSpec') ? $this->repeaterSpec((string) $name)?->min : null) ?? 0);
    $rowErrors = ($errors ?? null) instanceof \Illuminate\Support\ViewErrorBag ? $errors->getBag('default') : null;
    $rowError = $rowErrors?->first($name.'.'.$index);
    $rowError = filled($rowError) ? $rowError : null;
    $errorId = $titleId.'-error';
@endphp

<li wire:key="{{ $name }}-row-{{ $key }}" data-repeater-key="{{ $key }}" x-bind:class="dragging === {{ $keyScript }} ? 'opacity-50' : ''" x-on:dragover="allowDrop($event)" x-on:drop.prevent="drop({{ $keyScript }})">
    <div role="group" aria-labelledby="{{ $titleId }}" tabindex="-1" data-repeater-row {{ $attributes->merge(array_filter(['aria-describedby' => $rowError === null ? null : $errorId]))->class(['flex items-start gap-3 rounded-surface border border-gray-200 bg-white p-4 text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:focus-visible:outline-primary-400']) }}>
        <span aria-hidden="true" draggable="true" class="mt-0.5 cursor-grab text-gray-500 dark:text-gray-400" x-on:dragstart="startDrag($event, {{ $keyScript }})" x-on:dragend="dragging = null">
            <x-ui::icon name="fas-grip-vertical" />
        </span>

        <div class="min-w-0 flex-1 space-y-3">
            <p id="{{ $titleId }}" data-repeater-title class="text-sm font-medium">{{ $title }}</p>
            {{ $slot }}

            @if ($rowError !== null)
                <p id="{{ $errorId }}" class="text-sm text-rose-700 dark:text-rose-400">{{ $rowError }}</p>
            @endif
        </div>

        <div class="flex shrink-0 gap-1">
            <x-ui::button variant="ghost" size="sm" icon="fas-arrow-up" :label="__('ui::ui.repeater.move_up', ['row' => $title])" :disabled="$index === 0" data-repeater-action="up" x-on:click="move({{ $keyScript }}, 'up')" />
            <x-ui::button variant="ghost" size="sm" icon="fas-arrow-down" :label="__('ui::ui.repeater.move_down', ['row' => $title])" :disabled="$index === count($rows) - 1" data-repeater-action="down" x-on:click="move({{ $keyScript }}, 'down')" />
            <x-ui::button variant="ghost" size="sm" icon="fas-trash" :label="__('ui::ui.repeater.remove', ['row' => $title])" :disabled="! $canRemove" data-repeater-action="remove" x-on:click="remove({{ $keyScript }}, {{ $index }})" />
        </div>
    </div>
</li>
