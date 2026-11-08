@props(['title' => null, 'description' => null, 'breadcrumbs' => [], 'level' => 1])

@php
    throw_if(blank($title), \InvalidArgumentException::class, 'A ui::page-header needs a title.');

    $headingTag = 'h'.min(6, max(1, (int) $level));
    $crumbs = array_values($breadcrumbs);
    $lastCrumb = count($crumbs) - 1;
    $hasActions = isset($actions) && filled(trim((string) $actions));
@endphp

<div {{ $attributes->class(['space-y-2']) }}>
    @if ($crumbs !== [])
        <nav aria-label="{{ __('ui::ui.breadcrumbs') }}">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                @foreach ($crumbs as $index => $crumb)
                    <li class="flex items-center gap-1.5">
                        @if ($index === $lastCrumb)
                            <span aria-current="page" class="font-medium text-gray-900 dark:text-gray-100">{{ $crumb['label'] }}</span>
                        @elseif (filled($crumb['href'] ?? null))
                            <a href="{{ $crumb['href'] }}" class="rounded-control underline-offset-2 hover:text-gray-900 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:hover:text-gray-100 dark:focus-visible:outline-primary-400">{{ $crumb['label'] }}</a>
                        @else
                            <span>{{ $crumb['label'] }}</span>
                        @endif
                        @if ($index !== $lastCrumb)
                            <x-ui::icon name="fas-chevron-right" size="sm" />
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 space-y-1">
            <{{ $headingTag }} class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</{{ $headingTag }}>
            @if (filled($description))
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>

        @if ($hasActions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endif
    </div>
</div>
