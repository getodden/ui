@props(['label' => null, 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'required' => false])

@php
    $field = \Odden\Ui\Support\FieldState::resolve($attributes, $label, $name, $id, $error, filled($hint), $errors ?? null);
    $borderClasses = $field->error === null ? 'border-gray-400 dark:border-gray-500' : 'border-rose-700 dark:border-rose-400';
    $controlAttributes = $field->controlAttributes($attributes, $required)->class(["block w-full cursor-pointer rounded-control border bg-white p-1.5 text-sm text-gray-900 file:mr-3 file:cursor-pointer file:rounded-control file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-900 hover:file:bg-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-gray-100 dark:file:bg-gray-800 dark:file:text-gray-100 dark:hover:file:bg-gray-700 dark:focus-visible:outline-primary-400", $borderClasses]);
@endphp

<x-ui::field :id="$field->id" :label="$label" :hint="$hint" :error="$field->error" :required="$required">
    <input type="file" {{ $controlAttributes }}>
</x-ui::field>
