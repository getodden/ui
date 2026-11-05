@props(['name' => null, 'heading' => null, 'description' => null, 'size' => 'lg'])

@php
    throw_if(blank($heading), \InvalidArgumentException::class, 'A ui::modal needs a heading.');

    $baseId = 'ui-modal-'.($name === null ? \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)) : substr(sha1($name), 0, 12));
    $hasDescription = filled($description);
    $hasFooter = isset($footer) && filled(trim((string) $footer));

    $sizeClasses = match ($size) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'xl' => 'max-w-3xl',
        default => 'max-w-lg',
    };
@endphp

<div
    x-data="{
        open: false,
        name: @js($name),
        opener: null,
        focusDelay: 25,
        layer: 50,
        remember() {
            this.opener = document.activeElement
            window.uiModalLayer = (window.uiModalLayer ?? this.layer) + 1
            this.$refs.dialog.style.zIndex = window.uiModalLayer
            setTimeout(() => this.focusInitial(), this.focusDelay)
        },
        focusInitial() {
            const dialog = this.$refs.dialog
            if (!this.open) return
            if (getComputedStyle(dialog).display === 'none') {
                requestAnimationFrame(() => this.focusInitial())
                return
            }
            const target = dialog.querySelector('[data-autofocus]')
            if (target) target.focus()
            else this.$focus.within(dialog).first()
        },
        restore() {
            const opener = this.opener
            this.opener = null
            if (opener) this.$nextTick(() => opener.isConnected && opener.focus())
        },
        destroy() {
            const opener = this.open ? this.opener : null
            if (opener) setTimeout(() => opener.isConnected && opener.focus())
        }
    }"
    x-modelable="open"
    x-on:ui-modal-open.window="if (name !== null && $event.detail?.name === name) open = true"
    x-on:ui-modal-close.window="if (name !== null && $event.detail?.name === name) open = false"
    {{ $attributes }}
>
<template x-teleport="body">
    <div
        x-ref="dialog"
        x-show="open"
        x-effect="open ? remember() : restore()"
        x-trap.noscroll.noautofocus.noreturn="open"
        x-on:keydown.escape.stop="open = false"
        style="display: none"
        role="dialog"
        @if ($name !== null) data-ui-modal="{{ $name }}" @endif
        aria-modal="true"
        aria-labelledby="{{ $baseId }}-heading"
        @if ($hasDescription) aria-describedby="{{ $baseId }}-description" @endif
        tabindex="-1"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <div class="fixed inset-0 bg-gray-950/60" x-on:click="open = false" aria-hidden="true"></div>

        <div class="relative flex max-h-full w-full flex-col overflow-hidden rounded-surface border border-gray-200 bg-white text-gray-900 shadow-xl dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 {{ $sizeClasses }}">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h2 id="{{ $baseId }}-heading" class="text-base font-semibold">{{ $heading }}</h2>
                <x-ui::button variant="ghost" size="sm" icon="fas-xmark" :label="__('ui::ui.close')" x-on:click="open = false" class="-m-1" />
            </div>

            <div class="overflow-y-auto px-5 py-4">
                @if ($hasDescription)
                    <p id="{{ $baseId }}-description" class="mb-3 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
                @endif
                {{ $slot }}
            </div>

            @if ($hasFooter)
                <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-800 dark:bg-gray-950">{{ $footer }}</div>
            @endif
        </div>
    </div>
</template>
</div>
