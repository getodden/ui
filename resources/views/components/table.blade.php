@props(['caption' => null, 'captionVisible' => false, 'columns' => [], 'state' => null, 'records' => null, 'empty' => null, 'toolbar' => null, 'selectableKeys' => null, 'rowActions' => false])

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
    $selectable = $selectableKeys !== null;
    $pageKeys = $selectable ? array_values(array_map(strval(...), collect($selectableKeys)->all())) : [];
    $hasTabs = $state !== null && $state->tabs !== [];
    $bulkActions = $selectable && $state !== null ? $state->bulkActions : [];
    $selectedCount = $state?->selectedCount ?? 0;
    $columnCount = max(1, $columns->count()) + ($selectable ? 1 : 0) + ($rowActions ? 1 : 0);
    $targets = 'sortBy,resetFilters,setTab,runBulkAction,search,sort,direction,filters,tab,perPage,gotoPage,nextPage,previousPage,setPage';
    $checkboxClasses = 'size-4 rounded-control border-gray-400 accent-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:border-gray-500 dark:accent-primary-400 dark:focus-visible:outline-primary-400';
    $tabClasses = 'rounded-control px-3 py-1.5 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:focus-visible:outline-primary-400';
@endphp

<div
    class="space-y-4"
    data-record-table
    x-data="{
        lastFocused: null,
        timer: null,
        recoverFocus() {
            const last = this.lastFocused
            if (! last || last.isConnected) return
            const active = document.activeElement
            if (active && active !== document.body && (typeof active.checkVisibility !== 'function' || active.checkVisibility())) return
            this.lastFocused = null
            this.$el.querySelector('[data-select-page]:not(:disabled), thead button')?.focus()
        }
    }"
    x-init="new MutationObserver(() => { clearTimeout(timer); timer = setTimeout(() => recoverFocus(), 50) }).observe($el, { childList: true, subtree: true })"
    x-on:focusin="lastFocused = $event.target"
    x-on:focusin.window="if (! $el.contains($event.target) && ! $event.target.closest('[role=dialog]')) lastFocused = null"
>
    @if ($hasTabs)
        <nav aria-label="{{ __('ui::ui.table.tabs') }}">
            <ul class="flex flex-wrap gap-1 border-b border-gray-200 pb-2 dark:border-gray-800">
                @foreach ($state->tabs as $tabKey => $tabLabel)
                    <li>
                        <button type="button" wire:click="setTab({{ \Illuminate\Support\Js::from((string) $tabKey) }})" @if ($state->isActiveTab((string) $tabKey)) aria-current="true" @endif @class([$tabClasses, 'bg-primary-700 text-white dark:bg-primary-400 dark:text-gray-950' => $state->isActiveTab((string) $tabKey), 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => ! $state->isActiveTab((string) $tabKey)])>{{ $tabLabel }}</button>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if ($hasToolbar)
        <div class="flex flex-wrap items-end gap-3">{{ $toolbar }}</div>
    @endif

    @if ($bulkActions !== [])
        <div data-bulk-slot class="min-h-12">
            @if ($selectedCount > 0)
                <div role="group" aria-label="{{ __('ui::ui.table.bulk_actions') }}" class="flex flex-wrap items-center gap-2 rounded-surface border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-900 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100">
                    <span aria-hidden="true" class="font-medium">{{ trans_choice('ui::ui.table.selected', $selectedCount) }}</span>
                    @foreach ($bulkActions as $method => $actionLabel)
                        <x-ui::button size="sm" :variant="$state->isDangerousBulkAction((string) $method) ? 'danger' : 'secondary'" x-on:click="$wire.runBulkAction({{ \Illuminate\Support\Js::from((string) $method) }})">{{ $actionLabel }}</x-ui::button>
                    @endforeach
                    <x-ui::button size="sm" variant="ghost" x-on:click="$wire.clearSelection()">{{ __('ui::ui.table.clear_selection') }}</x-ui::button>
                </div>
            @endif
        </div>
    @endif

    <div role="status" class="sr-only">
        <span wire:loading wire:target="{{ $targets }}" class="hidden">{{ __('ui::ui.table.loading') }}</span>
        @if ($selectable)
            <span>{{ trans_choice('ui::ui.table.selected', $selectedCount) }}</span>
        @endif
        @if ($paginator !== null && $paginator->total() > 0)
            <span>{{ __('ui::ui.table.showing', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}</span>
        @endif
    </div>

    <div class="relative overflow-x-auto rounded-surface border border-gray-200 bg-white text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100">
        <table {{ $attributes->class(['w-full border-collapse text-start text-sm transition-opacity']) }} wire:loading.attr="aria-busy" wire:loading.delay.class="opacity-60" wire:target="{{ $targets }}">
            <caption @class(['px-4 py-3 text-start text-sm font-semibold', 'sr-only' => ! $captionVisible])>{{ $caption }}</caption>
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
                <tr>
                    @if ($selectable)
                        <th scope="col" class="w-10 px-4 py-3">
                            <input
                                type="checkbox"
                                data-select-page
                                x-data="{ sync() { const keys = JSON.parse($el.dataset.keys); const chosen = $wire.selected ?? []; $el.checked = keys.length > 0 && keys.every((key) => chosen.includes(key)); $el.indeterminate = ! $el.checked && keys.some((key) => chosen.includes(key)) } }"
                                x-init="new MutationObserver(() => sync()).observe($el, { attributes: true, attributeFilter: ['data-keys'] })"
                                aria-label="{{ __('ui::ui.table.select_page') }}"
                                data-keys="{{ json_encode($pageKeys) }}"
                                @disabled($pageKeys === [])
                                class="{{ $checkboxClasses }}"
                                x-effect="sync()"
                                x-on:change="const keys = JSON.parse($el.dataset.keys); const chosen = $wire.selected ?? []; $wire.$set('selected', $el.checked ? [...new Set([...chosen, ...keys])] : chosen.filter((key) => ! keys.includes(key)))"
                            >
                        </th>
                    @endif
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
                    @if ($rowActions)
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('ui::ui.table.row_actions') }}</span></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @if ($isEmpty)
                    <tr>
                        <td colspan="{{ $columnCount }}" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
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

        <div wire:loading.delay.flex wire:target="{{ $targets }}" aria-hidden="true" class="pointer-events-none absolute inset-0 hidden items-center justify-center">
            <x-ui::icon name="fas-spinner" size="lg" class="animate-spin text-primary-700 dark:text-primary-400" />
        </div>
    </div>

    @if ($paginator !== null && $paginator->hasPages())
        {{ $paginator->links('ui::partials.pagination') }}
    @endif
</div>
