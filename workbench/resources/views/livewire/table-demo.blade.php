<section aria-labelledby="table-demo-heading">
    <h2 id="table-demo-heading">Table</h2>

    <x-ui::table caption="Fruit" id="fruit-table" :state="$this->recordTableState()" :records="$records" :selectable-keys="$records->pluck('id')->all()" row-actions :columns="['name' => 'Name', 'colour' => 'Colour', 'price' => ['label' => 'Price', 'align' => 'end']]">
        <x-slot:toolbar>
            <x-ui::input id="fruit-search" type="search" wire:model.live.debounce.250ms="search" label="Search fruit" />
            <x-ui::select id="fruit-colour" wire:model.live="filters.colour" label="Colour" :options="$colours" placeholder="All colours" />
            <x-ui::select id="fruit-per-page" wire:model.live="perPage" label="Rows per page" :options="[10 => '10', 25 => '25']" />
            <x-ui::button id="fruit-unrelated" variant="secondary" wire:click="unrelatedAction">Unrelated</x-ui::button>
            <span id="fruit-unrelated-count">{{ $unrelated }}</span>
            <span id="fruit-last-action">{{ $lastAction }}</span>
        </x-slot:toolbar>

        @foreach ($records as $fruit)
            <tr wire:key="fruit-{{ $fruit->id }}">
                <td><x-ui::table.select :value="$fruit->id" :label="'Select '.$fruit->name" /></td>
                <th scope="row">{{ $fruit->name }}</th>
                <td>{{ $fruit->colour }}</td>
                <td>{{ $fruit->price }}</td>
                <td class="text-end">
                    <x-ui::table.actions :label="'Actions for '.$fruit->name">
                        <x-ui::table.action wire:click="inspect({{ \Illuminate\Support\Js::from($fruit->id) }})">Inspect</x-ui::table.action>
                    </x-ui::table.actions>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>No fruit matches your search.</x-slot:empty>
    </x-ui::table>

    <x-ui::confirm :confirmation="$confirmation" />
</section>
