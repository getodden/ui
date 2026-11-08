@props(['label' => null])

@php
    throw_if(blank($label), \InvalidArgumentException::class, 'A ui::table.actions needs a label.');
@endphp

<div
    class="relative inline-block text-start"
    x-data="{
        open: false,
        top: 0,
        right: 0,
        items() {
            return Array.from($refs.menu.querySelectorAll('[role=menuitem]:not([disabled])'))
        },
        show(index) {
            const box = $refs.trigger.getBoundingClientRect()
            this.top = box.bottom + 4
            this.right = window.innerWidth - box.right
            this.open = true
            this.$nextTick(() => this.items().at(index)?.focus())
        },
        hide(returnFocus) {
            if (! this.open) return
            this.open = false
            if (returnFocus) $refs.trigger.focus()
        },
        move(step) {
            const items = this.items()
            if (items.length === 0) return
            items[(items.indexOf(document.activeElement) + step + items.length) % items.length].focus()
        },
    }"
    x-on:keydown.escape="if (open) { $event.preventDefault(); hide(true) }"
    x-on:click.outside="hide(false)"
    x-on:scroll.window.capture="hide(false)"
    x-on:resize.window="hide(false)"
>
    <button
        type="button"
        x-ref="trigger"
        aria-haspopup="menu"
        aria-label="{{ $label }}"
        x-bind:aria-expanded="open"
        x-on:click="open ? hide(false) : show(0)"
        x-on:keydown.down.prevent="show(0)"
        x-on:keydown.up.prevent="show(-1)"
        class="inline-flex rounded-control p-2 text-gray-700 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:focus-visible:outline-primary-400"
    >
        <x-ui::icon name="fas-ellipsis-vertical" />
    </button>

    <div
        x-ref="menu"
        x-show="open"
        style="display: none"
        x-bind:style="{ position: 'fixed', top: top + 'px', right: right + 'px' }"
        role="menu"
        aria-label="{{ $label }}"
        x-on:click="hide(true)"
        x-on:keydown.down.prevent="move(1)"
        x-on:keydown.up.prevent="move(-1)"
        x-on:keydown.home.prevent="items().at(0)?.focus()"
        x-on:keydown.end.prevent="items().at(-1)?.focus()"
        x-on:keydown.tab="hide(false)"
        class="z-40 min-w-40 rounded-surface border border-gray-200 bg-white py-1 text-sm text-gray-900 shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100"
    >
        {{ $slot }}
    </div>
</div>
