@props(['name' => null, 'heading' => null, 'description' => null, 'size' => 'lg'])

@php
    $key = $name ?? \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $baseId = 'ui-modal-'.\Illuminate\Support\Str::slug($key);
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
    x-data="{ open: false, name: @js($name) }"
    x-modelable="open"
    x-on:ui-modal-open.window="if (name !== null && $event.detail?.name === name) open = true"
    x-on:ui-modal-close.window="if (name !== null && $event.detail?.name === name) open = false"
    x-on:keydown.escape.window="open = false"
    x-effect="open && setTimeout(() => $refs.dialog.querySelector('[data-autofocus]')?.focus(), 50)"
    {{ $attributes }}
>
    <div
        x-ref="dialog"
        x-show="open"
        x-trap.noscroll="open"
        style="display: none"
        role="dialog"
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
</div>
