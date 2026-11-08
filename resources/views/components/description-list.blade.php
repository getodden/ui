@props(['items' => []])

<dl {{ $attributes->class(['divide-y divide-gray-200 dark:divide-gray-800']) }}>
    @foreach ($items as $term => $details)
        <x-ui::description-list.item :label="(string) $term">{{ $details }}</x-ui::description-list.item>
    @endforeach
    {{ $slot }}
</dl>
