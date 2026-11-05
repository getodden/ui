@props(['id', 'label' => null, 'hint' => null, 'error' => null, 'required' => false, 'inline' => false])

<div class="space-y-1.5">
    @if ($inline)
        <div class="flex items-center gap-3">
            {{ $slot }}
            @includeWhen(filled($label), 'ui::partials.label')
        </div>
    @else
        @includeWhen(filled($label), 'ui::partials.label')
        {{ $slot }}
    @endif

    @if (filled($hint))
        <p id="{{ $id }}-hint" class="text-sm text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @if (filled($error))
        <p id="{{ $id }}-error" class="text-sm text-rose-700 dark:text-rose-400">{{ $error }}</p>
    @endif
</div>
