@props(['heading' => null, 'description' => null, 'icon' => null, 'level' => 3])

@php
    throw_if(blank($heading), \InvalidArgumentException::class, 'A ui::empty-state needs a heading.');

    $headingTag = 'h'.min(6, max(1, (int) $level));
    $hasActions = isset($actions) && filled(trim((string) $actions));
@endphp

<div {{ $attributes->class(['flex flex-col items-center gap-3 px-6 py-12 text-center']) }}>
    @if (filled($icon))
        <span class="flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
            <x-ui::icon :name="$icon" size="lg" />
        </span>
    @endif

    <{{ $headingTag }} class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $heading }}</{{ $headingTag }}>

    @if (filled($description))
        <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif

    @if ($hasActions)
        <div class="mt-2 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endif
</div>
