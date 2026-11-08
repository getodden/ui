<section aria-labelledby="forms-demo-heading">
    <x-ui::page-header
        id="forms-header"
        title="Forms"
        description="Every field the screens need"
        :breadcrumbs="[['label' => 'Home', 'href' => '/'], ['label' => 'Settings', 'href' => '/settings'], ['label' => 'Forms']]"
    >
        <x-slot:actions>
            <x-ui::dropdown id="forms-menu" label="Actions" align="end">
                <x-ui::dropdown.item id="menu-archive" icon="fas-check" wire:click="markAction('archive')">Archive</x-ui::dropdown.item>
                <x-ui::dropdown.item id="menu-export" href="#export">Export</x-ui::dropdown.item>
                <x-ui::dropdown.item id="menu-disabled" disabled>Locked</x-ui::dropdown.item>
                <x-ui::dropdown.item id="menu-delete" variant="danger" wire:click="markAction('delete')">Delete</x-ui::dropdown.item>
            </x-ui::dropdown>
        </x-slot:actions>
    </x-ui::page-header>
    <span id="last-action">{{ $lastAction }}</span>

    <form wire:submit="validateForm" id="forms-form">
        <x-ui::date label="Start date" wire:model="date" hint="Must be after 2030" />
        <x-ui::datetime label="Starts at" wire:model="datetime" />
        <x-ui::number label="Quantity" wire:model="quantity" min="0" step="1" />
        <x-ui::currency label="Amount" wire:model="amount" currency="USD" />
        <x-ui::checkbox label="I agree to the terms" wire:model="agreed" />
        <x-ui::radio-group label="Contact method" wire:model="contactMethod" :options="['email' => 'Email', 'phone' => ['label' => 'Phone', 'hint' => 'Weekdays only'], 'post' => 'Post']" hint="How we reach you" />
        <x-ui::multiselect label="Tags" wire:model="tags" :options="['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma', 'd' => 'Delta']" />
        <x-ui::combobox label="Customer" id="customer" wire:model="customer" search="searchCustomers" placeholder="Type a name" hint="Searches as you type" />
        <x-ui::file label="Attachment" wire:model="attachment" />
        <x-ui::button type="submit" id="forms-submit">Validate</x-ui::button>
    </form>

    <x-ui::collapsible id="forms-section" heading="Advanced options" description="Rarely needed" wire:model="sectionOpen">
        <p>Advanced body</p>
    </x-ui::collapsible>

    <x-ui::tabs id="forms-tabs" label="Record sections" wire:model="tab" :tabs="['details' => 'Details', 'notes' => 'Notes', 'files' => 'Files']">
        <x-ui::tab name="details">Details panel</x-ui::tab>
        <x-ui::tab name="notes">Notes panel</x-ui::tab>
        <x-ui::tab name="files">Files panel</x-ui::tab>
    </x-ui::tabs>

    <x-ui::description-list id="forms-details" :items="['Name' => 'Acme', 'Phone' => '']">
        <x-ui::description-list.item label="Owner">Ada</x-ui::description-list.item>
    </x-ui::description-list>

    <x-ui::empty-state id="forms-empty" icon="fas-inbox" heading="No contacts yet" description="Add your first contact to get started.">
        <x-slot:actions>
            <x-ui::button>Add contact</x-ui::button>
        </x-slot:actions>
    </x-ui::empty-state>
</section>
