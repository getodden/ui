<div
    role="status"
    aria-live="polite"
    x-data="{
        toasts: @js(\Odden\Ui\Support\Toasts::flashed()),
        counter: 0,
        init() {
            const initial = this.toasts
            this.toasts = []
            initial.forEach((toast) => this.add(toast))
        },
        add(toast) {
            const id = ++this.counter
            this.toasts.push({ id, message: toast.message, variant: toast.variant ?? 'success' })
            if (toast.variant !== 'danger') {
                setTimeout(() => this.dismiss(id), 5000)
            }
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id)
        },
    }"
    x-on:ui-toast.window="add($event.detail)"
    {{ $attributes->class(['pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-end gap-2 p-4']) }}
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
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
