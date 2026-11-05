<div>
    <h1>Odden UI demo</h1>

    <x-ui::card heading="Account">
        <form wire:submit="save">
            <x-ui::input label="Name" wire:model="name" hint="Shown on invoices" required />
            <x-ui::select label="Plan" wire:model="plan" :options="['starter' => 'Starter', 'growth' => 'Growth']" placeholder="Choose a plan" />
            <x-ui::textarea label="Notes" wire:model="notes" />
            <x-ui::toggle label="Email notifications" wire:model="notifications" />
            <x-ui::button type="submit" icon="fas-check">Save</x-ui::button>
        </form>

        <x-slot:footer>
            <x-ui::badge variant="success" icon="fas-check">Active</x-ui::badge>
            <x-ui::badge>Draft</x-ui::badge>
        </x-slot:footer>
    </x-ui::card>

    <x-ui::alert variant="info" heading="Heads up" dismissible>Informational message.</x-ui::alert>
    <x-ui::alert variant="danger">Something failed.</x-ui::alert>

    <x-ui::button id="open-edit" variant="outline" x-on:click="$dispatch('ui-modal-open', { name: 'edit' })">Open edit</x-ui::button>
    <x-ui::button id="warn" variant="secondary" wire:click="warn">Toast a warning</x-ui::button>
    <x-ui::button id="delete" variant="danger" wire:click="askDelete(7)">Delete</x-ui::button>
    <x-ui::button href="#top" variant="ghost">A link</x-ui::button>
    <x-ui::button icon="fas-check" label="Approve" variant="ghost" />

    <x-ui::modal name="edit" heading="Edit account" description="Change the account name.">
        <p>Modal body</p>
        <x-slot:footer>
            <x-ui::button variant="primary" wire:click="save">Save</x-ui::button>
        </x-slot:footer>
    </x-ui::modal>

    <x-ui::confirm :confirmation="$confirmation" />

    @if ($deletedId)
        <p id="deleted">Deleted item {{ $deletedId }}</p>
    @endif
</div>
