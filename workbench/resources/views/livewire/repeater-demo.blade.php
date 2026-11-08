<section aria-labelledby="repeater-demo-heading">
    <h2 id="repeater-demo-heading">Repeater</h2>

    <form wire:submit="save" id="repeater-form">
        <x-ui::repeater label="Phone numbers" name="phones" :rows="$phones" :min="1" :max="5" item-label="Phone" add-label="Add phone" hint="Up to five">
            @foreach ($phones as $index => $phone)
                <x-ui::repeater.row :index="$index">
                    <x-ui::input label="Label" wire:model="phones.{{ $index }}.label" />
                    <x-ui::input label="Number" wire:model="phones.{{ $index }}.number" />
                </x-ui::repeater.row>
            @endforeach
        </x-ui::repeater>

        <x-ui::button type="submit" id="repeater-save">Save phones</x-ui::button>
    </form>

    @if ($saved)
        <p id="repeater-saved">Saved</p>
    @endif

    <pre id="repeater-state">{{ json_encode(array_column($phones, 'label')) }}</pre>
</section>
