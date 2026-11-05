<section aria-labelledby="editor-demo-heading">
    <h2 id="editor-demo-heading">Editor</h2>

    <div id="editor-body">
        <x-ui::editor label="Body" wire:model.live="body" hint="Saved as HTML" :mentions="[['id' => '7', 'label' => 'Ada Lovelace'], ['id' => '8', 'label' => 'Adam Smith'], ['id' => 9, 'label' => 'Grace Hopper']]" />
    </div>
    <x-ui::button id="set-body" wire:click="setBodyFromServer">Set body from the server</x-ui::button>
    <pre id="saved-body">{{ $body }}</pre>

    <div id="editor-untrusted">
        <x-ui::editor label="Imported" wire:model="untrusted" />
    </div>

    <div id="editor-people">
        <x-ui::editor label="People" wire:model.live="peopleBody" :mentions="[['id' => '1', 'label' => 'Ada Lovelace'], ['id' => '2', 'label' => 'Adam Smith']]" :toolbar="[]" />
    </div>
    <pre id="saved-people">{{ $peopleBody }}</pre>

    <div id="editor-search">
        <x-ui::editor label="Search people" wire:model.live="searchBody" mention-search="searchPeople" :toolbar="['bold']" />
    </div>
    <pre id="saved-search">{{ $searchBody }}</pre>

    <div id="editor-json">
        <x-ui::editor label="Document" wire:model.live="mentionDoc" format="json" :mentions="[['id' => '7', 'label' => 'Ada Lovelace']]" />
    </div>
    <pre id="saved-json">{{ json_encode($mentionDoc) }}</pre>

    <div id="editor-filament">
        <x-ui::editor label="Filament content" wire:model="filament" />
    </div>
    <pre id="saved-filament">{{ $filament }}</pre>

    <div id="editor-unreadable">
        <x-ui::editor label="Unreadable" wire:model.live="unreadableDoc" format="json" />
    </div>
    <pre id="saved-unreadable">{{ json_encode($unreadableDoc) }}</pre>

    <div id="editor-emptydoc">
        <x-ui::editor label="Empty document" wire:model.live="emptyDoc" format="json" />
    </div>
    <pre id="saved-emptydoc">{{ json_encode($emptyDoc) }}</pre>

    <div id="editor-blur">
        <x-ui::editor label="On blur" wire:model.blur="blurBody" :toolbar="[]" />
    </div>
    <pre id="saved-blur">{{ $blurBody }}</pre>

    <div id="editor-liveblur">
        <x-ui::editor label="Live on blur" wire:model.live.blur="liveBlurBody" :toolbar="[]" />
    </div>
    <pre id="saved-liveblur">{{ $liveBlurBody }}</pre>

    <div id="editor-linkdoc">
        <x-ui::editor label="Link document" wire:model.live="linkDoc" format="json" />
    </div>
    <pre id="saved-linkdoc">{{ json_encode($linkDoc) }}</pre>

    <div id="editor-debounced">
        <x-ui::editor label="Debounced" wire:model.live.debounce.500ms="debounced" :toolbar="[]" />
    </div>
    <pre id="saved-debounced">{{ $debounced }}</pre>

    <div id="editor-odd">
        <x-ui::editor id="x'+alert(1)+'" label="Odd id" wire:model="oddBody" :mentions="[['id' => '1', 'label' => 'Ada Lovelace']]" />
    </div>

    <div id="editor-error">
        <x-ui::editor label="Required body" wire:model="emptyBody" error="Write something" required />
    </div>
</section>
