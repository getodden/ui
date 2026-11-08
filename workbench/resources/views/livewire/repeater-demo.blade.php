<section aria-labelledby="repeater-demo-heading">
    <h2 id="repeater-demo-heading">Repeater</h2>

    <form wire:submit="save" id="repeater-form">
        <x-ui::repeater label="Phone numbers" name="phones" :rows="$phones" item-label="Phone" add-label="Add phone" hint="Up to five">
            @foreach ($phones as $index => $phone)
                <x-ui::repeater.row :index="$index">
                    <x-ui::input label="Label" wire:model="phones.{{ $index }}.label" />
                    <x-ui::input label="Number" wire:model="phones.{{ $index }}.number" />
                </x-ui::repeater.row>
            @endforeach
        </x-ui::repeater>

        <x-ui::button type="submit" id="repeater-save">Save phones</x-ui::button>
    </form>

    <div id="nickname-repeater">
        <x-ui::repeater label="Nicknames" name="nicknames" :rows="$nicknames" item-label="Nickname" add-label="Add nickname">
            @foreach ($nicknames as $index => $nickname)
                <x-ui::repeater.row :index="$index">
                    <x-ui::input label="Label" wire:model="nicknames.{{ $index }}.label" />
                </x-ui::repeater.row>
            @endforeach
        </x-ui::repeater>
    </div>

    @if ($saved)
        <p id="repeater-saved">Saved</p>
    @endif

    <pre id="repeater-state">{{ json_encode(array_column($phones, 'label')) }}</pre>
</section>
