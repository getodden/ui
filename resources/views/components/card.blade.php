@props(['heading' => null, 'level' => 2, 'footer' => null])

@php
    $hasHeading = filled(trim((string) $heading));
    $hasFooter = filled(trim((string) $footer));
    $headingTag = 'h'.min(6, max(1, (int) $level));
    $headingId = 'ui-card-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
@endphp

<section @if ($hasHeading) aria-labelledby="{{ $headingId }}" @endif {{ $attributes->class(['rounded-surface border border-gray-200 bg-white text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100']) }}>
    @if ($hasHeading)
        <{{ $headingTag }} id="{{ $headingId }}" class="border-b border-gray-200 px-5 py-4 text-base font-semibold dark:border-gray-800">{{ $heading }}</{{ $headingTag }}>
    @endif

    <div class="p-5">{{ $slot }}</div>

    @if ($hasFooter)
        <div class="rounded-b-surface border-t border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-800 dark:bg-gray-950">{{ $footer }}</div>
    @endif
</section>
