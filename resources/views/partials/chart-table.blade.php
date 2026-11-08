@if ($togglable)
    <button type="button" aria-controls="{{ $tableId }}" aria-expanded="false" x-bind:aria-expanded="tableOpen" x-on:click="tableOpen = ! tableOpen" x-text="tableOpen ? {{ \Illuminate\Support\Js::from(__('ui::ui.chart.hide_table')) }} : {{ \Illuminate\Support\Js::from(__('ui::ui.chart.show_table')) }}" class="rounded-control text-sm font-medium text-primary-700 underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:text-primary-400 dark:focus-visible:outline-primary-400">{{ __('ui::ui.chart.show_table') }}</button>
@endif

<div id="{{ $tableId }}" class="sr-only" @if ($togglable) x-bind:class="{ 'sr-only': ! tableOpen }" @endif>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-start text-sm tabular-nums text-gray-900 dark:text-gray-100">
            <caption class="sr-only">{{ $caption }}</caption>
            <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-700 dark:border-gray-800 dark:text-gray-300">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col" @class(['px-3 py-2 font-semibold', 'text-start' => $loop->first, 'text-end' => ! $loop->first])>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            @if ($loop->first)
                                <th scope="row" class="px-3 py-2 text-start font-medium">{{ $cell }}</th>
                            @else
                                <td class="px-3 py-2 text-end">{{ $cell }}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
