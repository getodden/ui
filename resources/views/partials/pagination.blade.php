@php
    $pageName = $paginator->getPageName();
    $pageNameJs = \Illuminate\Support\Js::from($pageName);
    $buttonClasses = 'inline-flex min-w-9 items-center justify-center rounded-control border border-gray-400 bg-white px-3 py-1.5 text-sm font-medium text-gray-900 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-500 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800 dark:focus-visible:outline-primary-400';
    $currentClasses = 'inline-flex min-w-9 items-center justify-center rounded-control border border-primary-700 bg-primary-700 px-3 py-1.5 text-sm font-medium text-white dark:border-primary-400 dark:bg-primary-400 dark:text-gray-950';
@endphp

@if ($paginator->hasPages())
    <nav aria-label="{{ __('ui::ui.table.pagination') }}" class="flex flex-wrap items-center justify-center gap-1">
        <button type="button" @if ($paginator->onFirstPage()) aria-disabled="true" @else wire:click="previousPage({{ $pageNameJs }})" @endif class="{{ $buttonClasses }} aria-disabled:cursor-not-allowed aria-disabled:opacity-50">{{ __('ui::ui.table.previous') }}</button>

        @foreach ($elements as $element)
            @if (is_string($element))
                <span aria-hidden="true" class="px-2 text-gray-500 dark:text-gray-400">{{ $element }}</span>
            @elseif (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <button type="button" aria-current="page" class="{{ $currentClasses }}"><span class="sr-only">{{ __('ui::ui.table.page') }} </span>{{ $page }}</button>
                    @else
                        <button type="button" wire:click="gotoPage({{ $page }}, {{ $pageNameJs }})" aria-label="{{ __('ui::ui.table.go_to_page', ['page' => $page]) }}" class="{{ $buttonClasses }}">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        <button type="button" @if ($paginator->hasMorePages()) wire:click="nextPage({{ $pageNameJs }})" @else aria-disabled="true" @endif class="{{ $buttonClasses }} aria-disabled:cursor-not-allowed aria-disabled:opacity-50">{{ __('ui::ui.table.next') }}</button>
    </nav>
@endif
