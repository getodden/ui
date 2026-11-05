@props(['caption' => null, 'captionVisible' => false, 'columns' => [], 'state' => null, 'records' => null, 'empty' => null, 'toolbar' => null])

@php
    throw_if(
        blank($caption) && ! $attributes->has('aria-label') && ! $attributes->has('aria-labelledby'),
        \InvalidArgumentException::class,
        'A ui::table needs a caption, an aria-label or an aria-labelledby.'
    );

    $paginator = $records instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $records : null;
    $isEmpty = match (true) {
        $records === null => false,
        $paginator !== null => $paginator->isEmpty(),
        is_countable($records) => count($records) === 0,
        is_iterable($records) => collect($records)->isEmpty(),
        default => false,
    };
    $hasToolbar = filled(trim((string) $toolbar));
    $hasEmptySlot = filled(trim((string) $empty));
    $columns = collect($columns)->map(fn ($column): array => is_array($column) ? $column : ['label' => (string) $column]);
@endphp

<div class="space-y-4">
    @if ($hasToolbar)
        <div class="flex flex-wrap items-end gap-3">{{ $toolbar }}</div>
    @endif

    <div role="status" class="sr-only">
        <span wire:loading class="hidden">{{ __('ui::ui.table.loading') }}</span>
        @if ($paginator !== null && $paginator->total() > 0)
            <span>{{ __('ui::ui.table.showing', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}</span>
        @endif
    </div>

    <div class="relative overflow-x-auto rounded-surface border border-gray-200 bg-white text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100">
        <table {{ $attributes->class(['w-full border-collapse text-start text-sm transition-opacity']) }} wire:loading.attr="aria-busy" wire:loading.delay.class="opacity-60">
            <caption @class(['px-4 py-3 text-start text-sm font-semibold', 'sr-only' => ! $captionVisible])>{{ $caption }}</caption>
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
                <tr>
                    @foreach ($columns as $key => $column)
                        @php
                            $key = (string) $key;
                            $sortable = $state !== null && $state->isSortable($key);
                            $ariaSort = $state?->ariaSort($key);
                            $iconName = match ($ariaSort) {
                                'ascending' => 'fas-sort-up',
                                'descending' => 'fas-sort-down',
                                default => 'fas-sort',
                            };
                        @endphp
                        <th scope="col" @if ($ariaSort !== null) aria-sort="{{ $ariaSort }}" @endif @class(['px-4 py-3 font-semibold', 'text-end' => ($column['align'] ?? 'start') === 'end'])>
                            @if ($sortable)
                                <button type="button" wire:click="sortBy({{ \Illuminate\Support\Js::from($key) }})" @class(['inline-flex items-center gap-1.5 rounded-control uppercase tracking-wide focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:focus-visible:outline-primary-400', 'flex-row-reverse' => ($column['align'] ?? 'start') === 'end'])>
                                    {{ $column['label'] }}
                                    <x-ui::icon :name="$iconName" size="sm" :class="$ariaSort === null ? 'text-gray-500 dark:text-gray-400' : ''" />
                                </button>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @if ($isEmpty)
                    <tr>
                        <td colspan="{{ max(1, $columns->count()) }}" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            @if ($hasEmptySlot)
                                {{ $empty }}
                            @else
                                {{ __('ui::ui.table.empty') }}
                            @endif
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>

        <div wire:loading.delay.flex aria-hidden="true" class="pointer-events-none absolute inset-0 hidden items-center justify-center">
            <x-ui::icon name="fas-spinner" size="lg" class="animate-spin text-primary-700 dark:text-primary-400" />
        </div>
    </div>

    @if ($paginator !== null && $paginator->hasPages())
        {{ $paginator->links('ui::partials.pagination') }}
    @endif
</div>
