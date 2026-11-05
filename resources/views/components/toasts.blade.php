@props(['duration' => 5000])

<div
    role="status"
    aria-live="polite"
    aria-atomic="false"
    tabindex="-1"
    x-data="{
        toasts: @js(\Odden\Ui\Support\Toasts::flashed()),
        timers: {},
        counter: 0,
        root: null,
        init() {
            this.root = this.$el
            const initial = this.toasts
            this.toasts = []
            initial.forEach((toast) => this.add(toast))
        },
        add(toast) {
            const id = ++this.counter
            this.toasts.push({ id, message: toast.message, variant: toast.variant ?? 'success' })
            this.schedule(id)
        },
        schedule(id) {
            const toast = this.toasts.find((candidate) => candidate.id === id)
            if (!toast || toast.variant === 'danger' || this.timers[id] || this.interacting()) return
            this.timers[id] = setTimeout(() => this.dismiss(id), {{ (int) $duration }})
        },
        interacting() {
            return this.root.matches(':hover') || this.root.contains(document.activeElement)
        },
        pause() {
            Object.values(this.timers).forEach(clearTimeout)
            this.timers = {}
        },
        resume() {
            this.toasts.forEach((toast) => this.schedule(toast.id))
        },
        resumeSoon() {
            setTimeout(() => this.resume(), 0)
        },
        dismiss(id) {
            clearTimeout(this.timers[id])
            delete this.timers[id]
            const index = this.toasts.findIndex((toast) => toast.id === id)
            const element = this.root.querySelector(`[data-toast-id='${id}']`)
            const hadFocus = element?.contains(document.activeElement)
            this.toasts = this.toasts.filter((toast) => toast.id !== id)
            if (hadFocus) {
                this.$nextTick(() => {
                    const buttons = this.root.querySelectorAll('[data-toast-id] button')
                    const target = buttons[Math.min(Math.max(index - 1, 0), buttons.length - 1)] ?? this.root
                    target.focus()
                })
            }
            this.resumeSoon()
        },
    }"
    x-on:ui-toast.window="add($event.detail)"
    x-on:mouseenter="pause()"
    x-on:mouseleave="resumeSoon()"
    x-on:focusin="pause()"
    x-on:focusout="resumeSoon()"
    {{ $attributes->class(['pointer-events-none fixed outline-none inset-x-0 bottom-0 z-50 flex flex-col items-end gap-2 p-4']) }}
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            :data-toast-id="toast.id"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-surface border bg-white p-4 text-sm text-gray-900 shadow-lg dark:bg-gray-900 dark:text-gray-100"
            :class="{
                'border-emerald-200 dark:border-emerald-900': toast.variant === 'success',
                'border-sky-200 dark:border-sky-900': toast.variant === 'info',
                'border-amber-200 dark:border-amber-900': toast.variant === 'warning',
                'border-rose-200 dark:border-rose-900': toast.variant === 'danger',
            }"
        >
            <span x-show="toast.variant === 'success'" class="mt-0.5 text-emerald-700 dark:text-emerald-400"><x-ui::icon name="fas-circle-check" size="lg" /></span>
            <span x-show="toast.variant === 'info'" class="mt-0.5 text-sky-700 dark:text-sky-300"><x-ui::icon name="fas-circle-info" size="lg" /></span>
            <span x-show="toast.variant === 'warning'" class="mt-0.5 text-amber-800 dark:text-amber-300"><x-ui::icon name="fas-triangle-exclamation" size="lg" /></span>
            <span x-show="toast.variant === 'danger'" class="mt-0.5 text-rose-700 dark:text-rose-400"><x-ui::icon name="fas-circle-xmark" size="lg" /></span>

            <p class="min-w-0 flex-1" x-text="toast.message"></p>

            <x-ui::button variant="ghost" size="sm" icon="fas-xmark" :label="__('ui::ui.dismiss')" x-on:click="dismiss(toast.id)" class="-m-1" />
        </div>
    </template>
</div>
