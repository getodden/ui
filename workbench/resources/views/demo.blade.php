<div>
    <x-ui::confirm :confirmation="$confirmation" />

    <x-ui::modal name="inner" heading="Inner modal">
        <x-ui::button id="ping" wire:click="ping">Ping</x-ui::button>
    </x-ui::modal>
    <x-ui::modal name="outer" heading="Outer modal">
        <x-ui::button id="open-inner" x-on:click="$dispatch('ui-modal-open', { name: 'inner' })">Open inner</x-ui::button>
        <x-ui::button id="warn-in-outer" wire:click="warn">Warn</x-ui::button>
    </x-ui::modal>
    <x-ui::button id="open-outer" x-on:click="$dispatch('ui-modal-open', { name: 'outer' })">Open outer</x-ui::button>
    <span id="pings">{{ $pings }}</span>

    <h1>Odden UI demo</h1>

    <x-ui::card heading="Account">
        <form wire:submit="save">
            <x-ui::input label="Name" wire:model="name" hint="Shown on invoices" required />
            <x-ui::select label="Plan" wire:model="plan" :options="['small' => 'Small', 'medium' => 'Medium']" placeholder="Choose a plan" />
            <x-ui::textarea label="Notes" wire:model="notes" />
            <x-ui::toggle label="Email notifications" wire:model="notifications" />
            <x-ui::button type="submit" icon="fas-check">Save</x-ui::button>
        </form>

        <x-slot:footer>
            <x-ui::badge variant="success" icon="fas-check">Active</x-ui::badge>
            <x-ui::badge>Draft</x-ui::badge>
        </x-slot:footer>
    </x-ui::card>

    <x-ui::alert variant="info" id="first-alert" heading="Heads up" dismissible>Informational message.</x-ui::alert>
    <x-ui::alert variant="danger">Something failed.</x-ui::alert>

    <x-ui::button id="open-edit" variant="outline" x-on:click="$dispatch('ui-modal-open', { name: 'edit' })">Open edit</x-ui::button>
    <x-ui::button id="warn" variant="secondary" wire:click="warn">Toast a warning</x-ui::button>
    <x-ui::button id="delete" variant="danger" wire:click="askDelete(7)">Delete</x-ui::button>
    <x-ui::button href="#top" variant="ghost">A link</x-ui::button>
    <x-ui::button icon="fas-check" label="Approve" variant="ghost" />
    <x-ui::button id="open-server" wire:click="$set('showServer', true)">Open server</x-ui::button>
    <x-ui::button id="delete-a" variant="danger" wire:click="slowAction(1)">Slow A</x-ui::button>
    <x-ui::button id="delete-b" variant="danger" wire:click="slowAction(2)">Slow B</x-ui::button>
    <x-ui::button id="leave" variant="secondary" wire:click="saveAndLeave">Save and leave</x-ui::button>

    <x-ui::modal id="edit-modal" name="edit" heading="Edit account" description="Change the account name.">
        <p>Modal body</p>
        <x-ui::button id="delete-in-modal" variant="danger" wire:click="askDelete(8)">Delete in modal</x-ui::button>
        <x-ui::button id="open-details" variant="outline" x-on:click="$dispatch('ui-modal-open', { name: 'details' })">Open details</x-ui::button>
        <x-slot:footer>
            <x-ui::button variant="primary" wire:click="save">Save</x-ui::button>
        </x-slot:footer>
    </x-ui::modal>

    <x-ui::modal id="details-modal" name="details" heading="Account details">
        <p>Details body</p>
        <x-ui::button id="details-action" variant="outline">Details action</x-ui::button>
    </x-ui::modal>

    @if ($showServer)
        <x-ui::modal wire:model="showServer" heading="Server modal">
            <x-ui::button id="close-server" wire:click="closeServer">Close from server</x-ui::button>
        </x-ui::modal>
    @endif

    <x-ui::alert variant="info" id="second-alert" dismissible>Second alert</x-ui::alert>
    <input type="hidden" name="_token" value="x">
    <x-ui::button id="after-alerts" variant="ghost">After alerts</x-ui::button>

    @if ($deletedId)
        <p id="deleted">Deleted item {{ $deletedId }}</p>
    @endif
</div>
