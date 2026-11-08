@props(['label' => null, 'labelVisible' => false, 'segments' => []])

@php
    throw_if(
        blank($label),
        \InvalidArgumentException::class,
        'A ui::donut-chart needs a label.'
    );

    $segments = collect(is_array($segments) ? array_values($segments) : [])
        ->filter(fn (mixed $segment): bool => is_array($segment))
        ->map(fn (array $segment): array => ['label' => trim(\Odden\Ui\Support\ChartData::text($segment['label'] ?? '')), 'value' => \Odden\Ui\Support\ChartData::number($segment['value'] ?? null) ?? 0.0])
        ->filter(fn (array $segment): bool => $segment['value'] > 0)
        ->values();
    $overflow = $segments->count() > \Odden\Ui\Support\ChartData::MAXIMUM_SERIES ? $segments->slice(\Odden\Ui\Support\ChartData::MAXIMUM_SERIES - 1) : collect();
    $segments = $segments->take(\Odden\Ui\Support\ChartData::MAXIMUM_SERIES - ($overflow->isEmpty() ? 0 : 1))
        ->when($overflow->isNotEmpty(), fn ($kept) => $kept->push(['label' => __('ui::ui.chart.other'), 'value' => (float) $overflow->sum('value')]))
        ->map(fn (array $segment, int $index): array => ['label' => $segment['label'] !== '' ? $segment['label'] : __('ui::ui.chart.segment', ['number' => $index + 1]), 'value' => $segment['value']])
        ->all();
    $hasData = $segments !== [];
    $chartId = $attributes->get('id') ?? \Odden\Ui\Support\ChartData::identifier('donut', (string) $label);
    $captionId = $chartId.'-caption';

    if ($hasData) {
        $geometry = \Odden\Ui\Support\ChartGeometry::donut($segments);
        $totalText = \Odden\Ui\Support\ChartScale::compact($geometry['total']);
        $legend = collect($geometry['segments'])->map(fn (array $segment): array => ['text' => $segment['label'].': '.\Odden\Ui\Support\ChartScale::format($segment['value']).' ('.$segment['share'].')', 'paint' => \Odden\Ui\Support\ChartPalette::paint($segment['series'])])->all();
        $rows = collect($geometry['segments'])->map(fn (array $segment): array => [$segment['label'], \Odden\Ui\Support\ChartScale::format($segment['value']), $segment['share']])->all();
    }
@endphp

<figure {{ $attributes->class(['space-y-3'])->merge(['id' => $chartId]) }} @if ($hasData) x-data="{ tableOpen: false }" @endif>
    <figcaption id="{{ $captionId }}" @class(['text-sm font-semibold text-gray-900 dark:text-gray-100', 'sr-only' => ! $labelVisible])>{{ $label }}</figcaption>

    @if (! $hasData)
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('ui::ui.chart.empty') }}</p>
    @else
        <svg viewBox="0 0 120 120" role="img" aria-labelledby="{{ $captionId }}" class="mx-auto block h-auto w-40 max-w-full" focusable="false">
            @foreach ($geometry['segments'] as $segment)
                <path d="{{ $segment['path'] }}" fill-rule="evenodd" style="fill: {{ \Odden\Ui\Support\ChartPalette::paint($segment['series']) }}"><title>{{ $segment['label'] }}: {{ \Odden\Ui\Support\ChartScale::format($segment['value']) }} ({{ $segment['share'] }})</title></path>
            @endforeach
            <text x="60" y="64" text-anchor="middle" font-size="14" font-weight="600" class="fill-gray-900 dark:fill-gray-100">{{ $totalText }}</text>
        </svg>

        @include('ui::partials.chart-legend', ['entries' => $legend])

        @include('ui::partials.chart-table', [
            'tableId' => $chartId.'-table',
            'caption' => $label,
            'togglable' => true,
            'headers' => [__('ui::ui.chart.category'), __('ui::ui.chart.value'), __('ui::ui.chart.share')],
            'rows' => $rows,
        ])
    @endif
</figure>
