@props(['label' => null, 'labelVisible' => false, 'categories' => [], 'series' => []])

@php
    throw_if(
        blank($label),
        \InvalidArgumentException::class,
        'A ui::line-chart needs a label.'
    );

    $categories = \Odden\Ui\Support\ChartData::categories($categories);
    $series = \Odden\Ui\Support\ChartData::series($series, count($categories));
    $hasData = \Odden\Ui\Support\ChartData::hasValues($series);
    $chartId = $attributes->get('id') ?? \Odden\Ui\Support\ChartData::identifier('line', (string) $label);
    $captionId = $chartId.'-caption';

    if ($hasData) {
        $geometry = \Odden\Ui\Support\ChartGeometry::lines($categories, $series);
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
                <line x1="{{ $plot['left'] }}" x2="{{ $plot['right'] }}" y1="{{ $tick['position'] }}" y2="{{ $tick['position'] }}" stroke-width="1" class="stroke-gray-200 dark:stroke-gray-800" />
                <text x="{{ $plot['left'] - 6 }}" y="{{ $tick['position'] + 4 }}" text-anchor="end" font-size="11" class="fill-gray-600 dark:fill-gray-400">{{ $tick['label'] }}</text>
            @endforeach

            @foreach ($geometry['labels'] as $axisLabel)
                <text x="{{ $axisLabel['position'] }}" y="{{ $plot['bottom'] + 16 }}" text-anchor="middle" font-size="11" class="fill-gray-600 dark:fill-gray-400"><title>{{ $axisLabel['full'] }}</title>{{ $axisLabel['text'] }}</text>
            @endforeach

            @foreach ($geometry['lines'] as $line)
                @if ($line['path'] !== null)
                    <path d="{{ $line['path'] }}" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: {{ \Odden\Ui\Support\ChartPalette::paint($line['series']) }}" />
                @endif

                @foreach ($line['points'] as $point)
                    @if ($point['marker'])
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3" stroke-width="1.5" class="stroke-white dark:stroke-gray-900" style="fill: {{ \Odden\Ui\Support\ChartPalette::paint($line['series']) }}"><title>{{ $point['title'] }}</title></circle>
                    @endif
                @endforeach
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
