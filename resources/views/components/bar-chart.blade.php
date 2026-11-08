@props(['label' => null, 'labelVisible' => false, 'categories' => [], 'series' => [], 'orientation' => 'vertical', 'stacked' => false])

@php
    throw_if(
        blank($label),
        \InvalidArgumentException::class,
        'A ui::bar-chart needs a label.'
    );

    $categories = \Odden\Ui\Support\ChartData::categories($categories);
    $series = \Odden\Ui\Support\ChartData::series($series, count($categories));
    $hasData = \Odden\Ui\Support\ChartData::hasValues($series);
    $chartId = $attributes->get('id') ?? \Odden\Ui\Support\ChartData::identifier('bar', (string) $label);
    $captionId = $chartId.'-caption';

    if ($hasData) {
        $geometry = \Odden\Ui\Support\ChartGeometry::bars($categories, $series, $orientation === 'horizontal', (bool) $stacked);
        $horizontal = $geometry['horizontal'];
        $plot = $geometry['plot'];
        $legend = count($series) > 1
            ? collect($series)->map(fn (array $entry, int $index): array => ['text' => $entry['name'], 'paint' => \Odden\Ui\Support\ChartPalette::paint($index)])->all()
            : [];
    }
@endphp

<figure {{ $attributes->class(['space-y-3'])->merge(['id' => $chartId]) }} @if ($hasData) x-data="{ tableOpen: false }" @endif>
    <figcaption id="{{ $captionId }}" @class(['text-sm font-semibold text-gray-900 dark:text-gray-100', 'sr-only' => ! $labelVisible])>{{ $label }}</figcaption>

    @if (! $hasData)
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('ui::ui.chart.empty') }}</p>
    @else
        <svg viewBox="0 0 {{ $geometry['width'] }} {{ $geometry['height'] }}" role="img" aria-labelledby="{{ $captionId }}" class="block h-auto w-full" focusable="false">
            @foreach ($geometry['ticks'] as $tick)
                @if ($horizontal)
                    <line x1="{{ $tick['position'] }}" x2="{{ $tick['position'] }}" y1="{{ $plot['top'] }}" y2="{{ $plot['bottom'] }}" stroke-width="1" class="stroke-gray-200 dark:stroke-gray-800" />
                    <text x="{{ $tick['position'] }}" y="{{ $plot['bottom'] + 16 }}" text-anchor="middle" font-size="11" class="fill-gray-600 dark:fill-gray-400">{{ $tick['label'] }}</text>
                @else
                    <line x1="{{ $plot['left'] }}" x2="{{ $plot['right'] }}" y1="{{ $tick['position'] }}" y2="{{ $tick['position'] }}" stroke-width="1" class="stroke-gray-200 dark:stroke-gray-800" />
                    <text x="{{ $plot['left'] - 6 }}" y="{{ $tick['position'] + 4 }}" text-anchor="end" font-size="11" class="fill-gray-600 dark:fill-gray-400">{{ $tick['label'] }}</text>
                @endif
            @endforeach

            @foreach ($geometry['bars'] as $bar)
                <rect x="{{ $bar['x'] }}" y="{{ $bar['y'] }}" width="{{ $bar['width'] }}" height="{{ $bar['height'] }}" rx="2" style="fill: {{ \Odden\Ui\Support\ChartPalette::paint($bar['series']) }}"><title>{{ $bar['title'] }}</title></rect>
            @endforeach

            @if ($horizontal)
                <line x1="{{ $geometry['baseline'] }}" x2="{{ $geometry['baseline'] }}" y1="{{ $plot['top'] }}" y2="{{ $plot['bottom'] }}" stroke-width="1" class="stroke-gray-400 dark:stroke-gray-500" />
            @else
                <line x1="{{ $plot['left'] }}" x2="{{ $plot['right'] }}" y1="{{ $geometry['baseline'] }}" y2="{{ $geometry['baseline'] }}" stroke-width="1" class="stroke-gray-400 dark:stroke-gray-500" />
            @endif

            @foreach ($geometry['labels'] as $axisLabel)
                @if ($horizontal)
                    <text x="{{ $plot['left'] - 6 }}" y="{{ $axisLabel['position'] + 4 }}" text-anchor="end" font-size="11" class="fill-gray-600 dark:fill-gray-400"><title>{{ $axisLabel['full'] }}</title>{{ $axisLabel['text'] }}</text>
                @else
                    <text x="{{ $axisLabel['position'] }}" y="{{ $plot['bottom'] + 16 }}" text-anchor="{{ $axisLabel['anchor'] }}" font-size="11" class="fill-gray-600 dark:fill-gray-400"><title>{{ $axisLabel['full'] }}</title>{{ $axisLabel['text'] }}</text>
                @endif
            @endforeach
        </svg>

        @if ($legend !== [])
            @include('ui::partials.chart-legend', ['entries' => $legend])
        @endif

        @include('ui::partials.chart-table', [
            'tableId' => $chartId.'-table',
            'caption' => $label,
            'togglable' => true,
            'headers' => [__('ui::ui.chart.category'), ...array_column($series, 'name')],
            'rows' => \Odden\Ui\Support\ChartData::tableRows($categories, $series),
        ])
    @endif
</figure>
