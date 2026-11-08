@props(['label' => null, 'values' => []])

@php
    throw_if(
        blank($label),
        \InvalidArgumentException::class,
        'A ui::sparkline needs a label.'
    );

    $values = array_map(\Odden\Ui\Support\ChartData::number(...), array_values(\Odden\Ui\Support\ChartData::items($values)));
    \Odden\Ui\Support\ChartData::within(count($values), \Odden\Ui\Support\ChartData::MAXIMUM_CATEGORIES, 'values');
    $hasData = array_filter($values, fn (?float $value): bool => $value !== null) !== [];
    $chartId = $attributes->get('id') ?? \Odden\Ui\Support\ChartData::identifier('sparkline', (string) $label);
    $captionId = $chartId.'-caption';

    if ($hasData) {
        $geometry = \Odden\Ui\Support\ChartGeometry::sparkline($values);
        $tableRows = array_map(fn (int $position, ?float $value): array => [(string) ($position + 1), $value === null ? '' : \Odden\Ui\Support\ChartScale::format($value)], array_keys($values), $values);
    }
@endphp

<figure {{ $attributes->class(['inline-block align-middle'])->merge(['id' => $chartId]) }}>
    <figcaption id="{{ $captionId }}" class="sr-only">{{ $label }}</figcaption>

    @if (! $hasData)
        <span aria-hidden="true" class="text-gray-600 dark:text-gray-400">&ndash;</span>
        <span class="sr-only">{{ __('ui::ui.chart.empty') }}</span>
    @else
        <svg viewBox="0 0 {{ $geometry['width'] }} {{ $geometry['height'] }}" role="img" aria-labelledby="{{ $captionId }}" class="block h-7 w-24 text-primary-700 dark:text-primary-400" focusable="false">
            @if ($geometry['path'] !== null)
                <path d="{{ $geometry['path'] }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            @endif

            @foreach ($geometry['dots'] as $dot)
                <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="2.5" fill="currentColor" />
            @endforeach
        </svg>

        @include('ui::partials.chart-table', [
            'tableId' => $chartId.'-table',
            'caption' => $label,
            'togglable' => false,
            'headers' => [__('ui::ui.chart.category'), __('ui::ui.chart.value')],
            'rows' => $tableRows,
        ])
    @endif
</figure>
