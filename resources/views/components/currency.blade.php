@props(['label' => null, 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'required' => false, 'symbol' => '$', 'currency' => null])

@php
    $currencyId = \Odden\Ui\Support\FieldState::resolve($attributes, $label, $name, $id, $error, filled($hint), $errors ?? null)->id.'-currency';

    if (filled($currency)) {
        $attributes->setAttributes([...$attributes->getAttributes(), 'aria-describedby' => trim($attributes->get('aria-describedby', '').' '.$currencyId)]);
    }

    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $label, $name, $id, $error, filled($hint), $errors ?? null);
    $borderClasses = $field->error === null ? 'border-gray-400 dark:border-gray-500' : 'border-rose-700 dark:border-rose-400';
    $controlAttributes = $field->controlAttributes($attributes, $required)->merge(['step' => '0.01', 'inputmode' => 'decimal', 'style' => 'padding-left: calc(1.25rem + '.mb_strlen((string) $symbol).'ch)'])->class(["block w-full rounded-control border bg-white py-2 text-sm text-gray-900 placeholder:text-gray-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-400 dark:focus-visible:outline-primary-400", filled($currency) ? 'pr-14' : 'pr-3', $borderClasses]);
@endphp

<x-ui::field :id="$field->id" :label="$label" :hint="$hint" :error="$field->error" :required="$required">
    <div class="relative">
        <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500 dark:text-gray-400">{{ $symbol }}</span>
        <input type="number" {{ $controlAttributes }}>
        @if (filled($currency))
            <span id="{{ $currencyId }}" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm text-gray-500 dark:text-gray-400">{{ $currency }}</span>
        @endif
    </div>
</x-ui::field>
