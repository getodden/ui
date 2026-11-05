@props(['label' => null, 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'required' => false, 'options' => [], 'placeholder' => null, 'value' => null])

@php
    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $name, $id, $error, filled($hint), $errors ?? null);
    $borderClasses = $field->error === null ? 'border-gray-300 dark:border-gray-700' : 'border-rose-700 dark:border-rose-400';
    $controlAttributes = $attributes->merge(array_filter([
        'id' => $field->id,
        'name' => $field->name,
        'aria-describedby' => $field->describedBy,
        'aria-invalid' => $field->error === null ? null : 'true',
        'required' => $required ? true : null,
    ], fn ($value) => $value !== null))->class(["block w-full rounded-control border bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-400 dark:focus-visible:outline-primary-400", $borderClasses]);
@endphp

<x-ui::field :id="$field->id" :label="$label" :hint="$hint" :error="$field->error" :required="$required">
    <select {{ $controlAttributes }}>
        @if (filled($placeholder))
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($value !== null && (string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
</x-ui::field>
