@props(['label' => null, 'value' => null, 'delta' => null, 'direction' => null, 'sentiment' => null, 'deltaLabel' => null, 'sparkline' => []])

@php
    throw_if(
        blank($label),
        \InvalidArgumentException::class,
        'A ui::stat needs a label.'
    );

    $hasDelta = filled($delta);
    $direction = in_array($direction, ['up', 'down', 'flat'], true) ? $direction : match (true) {
        is_numeric($delta) && (float) $delta > 0 => 'up',
        is_numeric($delta) && (float) $delta < 0 => 'down',
        str_starts_with(trim((string) $delta), '+') => 'up',
        str_starts_with(trim((string) $delta), '-'), str_starts_with(trim((string) $delta), '−') => 'down',
        preg_match('/^[+\-−]?0+([.,]0+)?\s*%?$/u', trim((string) $delta)) === 1 => 'flat',
        default => null,
    };
    $sentiment = in_array($sentiment, ['positive', 'negative', 'neutral'], true) ? $sentiment : match ($direction) {
        'up' => 'positive',
        'down' => 'negative',
        default => 'neutral',
    };
    $deltaClasses = match ($sentiment) {
        'positive' => 'text-emerald-700 dark:text-emerald-400',
        'negative' => 'text-rose-700 dark:text-rose-400',
        default => 'text-gray-600 dark:text-gray-400',
    };
    $deltaIcon = match ($direction) {
        'up' => 'fas-arrow-up',
        'down' => 'fas-arrow-down',
        'flat' => 'fas-minus',
        default => null,
    };
    $hasSparkline = is_array($sparkline) && $sparkline !== [];
@endphp

<div {{ $attributes->class(['flex items-end justify-between gap-4 rounded-surface border border-gray-200 bg-white p-5 text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100']) }}>
    <dl class="min-w-0">
        <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $label }}</dt>
        <dd class="mt-1 text-3xl font-semibold tabular-nums">{{ $value }}</dd>
        @if ($hasDelta)
            <dd class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm">
                <span class="inline-flex items-center gap-1 font-medium {{ $deltaClasses }}">
                    @if ($deltaIcon !== null)
                        <x-ui::icon :name="$deltaIcon" size="sm" />
                        <span class="sr-only">{{ __('ui::ui.chart.'.$direction) }}</span>
                    @endif
                    <span class="tabular-nums">{{ $delta }}</span>
                    @if ($sentiment !== 'neutral')
                        <span class="sr-only">{{ __('ui::ui.chart.'.($sentiment === 'positive' ? 'favourable' : 'unfavourable')) }}</span>
                    @endif
                </span>
                @if (filled($deltaLabel))
                    <span class="text-gray-600 dark:text-gray-400">{{ $deltaLabel }}</span>
                @endif
            </dd>
        @endif
    </dl>
    @if ($hasSparkline)
        <x-ui::sparkline :label="__('ui::ui.chart.trend', ['label' => $label])" :values="$sparkline" />
    @endif
</div>
