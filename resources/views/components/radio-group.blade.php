@props(['label' => null, 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'required' => false, 'options' => [], 'value' => null])

@php
    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $label, $name, $id, $error, filled($hint), $errors ?? null);
    $groupName = $field->name ?? $field->id;
    $radioAttributes = $attributes->whereStartsWith('wire:model');
    $groupAttributes = $attributes->whereDoesntStartWith('wire:model')->except('aria-describedby');
    $groupAttributes = $field->describedBy === null ? $groupAttributes : $groupAttributes->merge(['aria-describedby' => $field->describedBy]);
    $radioClasses = 'mt-0.5 size-4 shrink-0 cursor-pointer accent-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:accent-primary-400 dark:focus-visible:outline-primary-400';
@endphp

<fieldset {{ $groupAttributes->class(['space-y-1.5']) }}>
    @if (filled($label))
        <legend id="{{ $field->id }}-label" class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}@if ($required) <span aria-hidden="true" class="text-rose-700 dark:text-rose-400">*</span>@endif</legend>
    @endif

    <div class="space-y-2">
        @foreach ($options as $optionValue => $option)
            @php
                $optionLabel = is_array($option) ? ($option['label'] ?? '') : $option;
                $optionHint = is_array($option) ? ($option['hint'] ?? null) : null;
                $optionId = $field->id.'-'.$loop->index;
            @endphp
            <div class="flex items-start gap-3">
                <input
                    type="radio"
                    id="{{ $optionId }}"
                    name="{{ $groupName }}"
                    value="{{ $optionValue }}"
                    @checked($value !== null && (string) $value === (string) $optionValue)
                    @required($required)
                    @if ($field->error !== null) aria-invalid="true" @endif
                    @if (filled($optionHint)) aria-describedby="{{ $optionId }}-hint" @endif
                    {{ $radioAttributes->class([$radioClasses]) }}
                >
                <span class="space-y-0.5">
                    <label for="{{ $optionId }}" class="block text-sm text-gray-900 dark:text-gray-100">{{ $optionLabel }}</label>
                    @if (filled($optionHint))
                        <span id="{{ $optionId }}-hint" class="block text-sm text-gray-500 dark:text-gray-400">{{ $optionHint }}</span>
                    @endif
                </span>
            </div>
        @endforeach
    </div>

    @if (filled($hint))
        <p id="{{ $field->id }}-hint" class="text-sm text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @if (filled($field->error))
        <p id="{{ $field->id }}-error" class="text-sm text-rose-700 dark:text-rose-400">{{ $field->error }}</p>
    @endif
</fieldset>
