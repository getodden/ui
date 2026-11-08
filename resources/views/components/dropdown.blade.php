@props(['id' => null, 'label' => null, 'icon' => null, 'iconOnly' => false, 'variant' => 'outline', 'align' => 'start', 'size' => 'md'])

@php
    throw_if(blank($label), \InvalidArgumentException::class, 'A ui::dropdown needs a label.');

    $baseId = filled($id) ? $id : 'ui-dropdown-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $alignClasses = $align === 'end' ? 'right-0' : 'left-0';
@endphp

<div
    class="relative inline-block"
    x-data="{
        open: false,
        items() {
            return [...this.$refs.menu.querySelectorAll('[role=menuitem]:not(:disabled)')]
        },
        show(position) {
            this.open = true
            this.$nextTick(() => {
                const items = this.items()
                if (items.length === 0) return
                items[position === 'last' ? items.length - 1 : 0].focus()
            })
        },
        close(restore = false) {
            if (!this.open) return
            this.open = false
            if (restore) this.$refs.trigger.focus()
        },
        move(step) {
            const items = this.items()
            if (items.length === 0) return
            const current = items.indexOf(document.activeElement)
            items[(current + step + items.length) % items.length].focus()
        },
        edge(last) {
            const items = this.items()
            if (items.length === 0) return
            items[last ? items.length - 1 : 0].focus()
        },
        typeahead(event) {
            if (event.key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey || event.key === ' ') return
            const items = this.items()
            const current = items.indexOf(document.activeElement)
            const ordered = [...items.slice(current + 1), ...items.slice(0, current + 1)]
            const match = ordered.find((item) => item.textContent.trim().toLowerCase().startsWith(event.key.toLowerCase()))
            if (match) match.focus()
        }
    }"
    x-on:keydown.escape="if (open) { $event.stopPropagation(); close(true) }"
    x-on:click.outside="close()"
>
    @php
        $triggerBindings = [
            'variant' => $variant,
            'size' => $size,
            'id' => $baseId.'-trigger',
            'aria-haspopup' => 'menu',
            'aria-controls' => $baseId.'-menu',
            'x-ref' => 'trigger',
            'x-bind:aria-expanded' => "open ? 'true' : 'false'",
            'x-on:click' => "open ? close() : show('first')",
            'x-on:keydown.arrow-down.prevent' => "show('first')",
            'x-on:keydown.arrow-up.prevent' => "show('last')",
        ];
    @endphp

    @if ($iconOnly)
        <x-ui::button :icon="$icon ?? 'fas-ellipsis'" :label="$label" {{ $attributes->merge($triggerBindings) }} />
    @else
        <x-ui::button :icon="$icon" {{ $attributes->merge($triggerBindings) }}>
            {{ $label }}
            <x-ui::icon name="fas-chevron-down" size="sm" />
        </x-ui::button>
    @endif

    <div
        id="{{ $baseId }}-menu"
        role="menu"
        aria-labelledby="{{ $baseId }}-trigger"
        x-ref="menu"
        x-show="open"
        style="display: none"
        x-on:keydown.arrow-down.prevent="move(1)"
        x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.home.prevent="edge(false)"
        x-on:keydown.end.prevent="edge(true)"
        x-on:keydown.tab="close(true)"
        x-on:keydown="typeahead($event)"
        x-on:click="if ($event.target.closest('[role=menuitem]')) close(true)"
        class="absolute z-40 mt-1 min-w-48 rounded-control border border-gray-200 bg-white py-1 shadow-popover dark:border-gray-700 dark:bg-gray-900 {{ $alignClasses }}"
    >
        {{ $slot }}
    </div>
</div>
