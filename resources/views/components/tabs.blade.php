@props(['id' => null, 'tabs' => [], 'selected' => null, 'label' => null])

@php
    throw_if(blank($id), \InvalidArgumentException::class, 'A ui::tabs needs an id.');
    throw_if(blank($label) && ! $attributes->has('aria-label') && ! $attributes->has('aria-labelledby'), \InvalidArgumentException::class, 'A ui::tabs needs a label, an aria-label or an aria-labelledby.');

    $keys = array_map('strval', array_keys($tabs));
    $initial = in_array((string) $selected, $keys, true) ? (string) $selected : ($keys[0] ?? '');
    $tabId = fn (string $key): string => $id.'-tab-'.substr(sha1($key), 0, 10);
    $panelId = fn (string $key): string => $id.'-panel-'.substr(sha1($key), 0, 10);
@endphp

<div id="{{ $id }}" x-data="{
        selected: '',
        init() {
            this.selected = this.$root.dataset.initial
            this.$watch('selected', () => this.normalize())
            this.$nextTick(() => {
                this.normalize()
                new MutationObserver(() => this.normalize()).observe(this.$root, { childList: true, subtree: true })
            })
        },
        normalize() {
            const keys = [...this.$root.querySelectorAll(':scope > [role=tablist] > [role=tab]')].map((tab) => tab.dataset.key)
            if (keys.length === 0) return
            const current = String(this.selected ?? '')
            const next = keys.includes(current) ? current : keys[0]
            if (this.selected !== next) this.selected = next
        }
    }" x-modelable="selected" data-initial="{{ $initial }}" {{ $attributes->whereDoesntStartWith('aria-')->whereDoesntStartWith('wire:model') }} {{ $attributes->whereStartsWith('wire:model') }}>
    <div
        role="tablist"
        @if (filled($label)) aria-label="{{ $label }}" @endif
        {{ $attributes->whereStartsWith('aria-') }}
        class="flex gap-1 border-b border-gray-200 dark:border-gray-800"
        x-on:keydown.arrow-right.prevent="focusTab(1)"
        x-on:keydown.arrow-left.prevent="focusTab(-1)"
        x-on:keydown.home.prevent="focusTab(0, true)"
        x-on:keydown.end.prevent="focusTab(-1, true)"
        x-data="{
            focusTab(step, absolute = false) {
                const tabs = [...$el.querySelectorAll('[role=tab]:not(:disabled)')]
                if (tabs.length === 0) return
                const current = tabs.indexOf(document.activeElement)
                const index = absolute ? (step < 0 ? tabs.length - 1 : 0) : (current + step + tabs.length) % tabs.length
                tabs[index].focus()
                selected = tabs[index].dataset.key
            }
        }"
    >
        @foreach ($tabs as $key => $tabLabel)
            <button
                type="button"
                role="tab"
                id="{{ $tabId((string) $key) }}"
                data-key="{{ $key }}"
                aria-controls="{{ $panelId((string) $key) }}"
                aria-selected="{{ (string) $key === $initial ? 'true' : 'false' }}"
                tabindex="{{ (string) $key === $initial ? '0' : '-1' }}"
                x-bind:aria-selected="selected === @js((string) $key) ? 'true' : 'false'"
                x-bind:tabindex="selected === @js((string) $key) ? 0 : -1"
                x-bind:class="selected === @js((string) $key) ? 'border-primary-700 text-primary-700 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100'"
                x-on:click="selected = @js((string) $key)"
                class="-mb-px rounded-t-control border-b-2 px-4 py-2 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:focus-visible:outline-primary-400"
            >{{ $tabLabel }}</button>
        @endforeach
    </div>

    {{ $slot }}
</div>
