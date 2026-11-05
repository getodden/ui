<?php

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Js;
use Illuminate\View\ViewException;
use Odden\Ui\Support\RecordTableState;
use Odden\Ui\Tests\Support\Html;

function paginatorOf(int $total, int $perPage = 10, int $page = 1): LengthAwarePaginator
{
    $items = array_slice($total === 0 ? [] : range(1, $total), ($page - 1) * $perPage, $perPage);

    return new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => '/']);
}

const TABLE_COLUMNS = ['name' => 'Name', 'colour' => 'Colour', 'price' => ['label' => 'Price', 'align' => 'end']];

it('throws without a caption, aria-label or aria-labelledby', function (): void {
    Html::render('<x-ui::table :columns="$columns"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);
})->throws(ViewException::class, 'A ui::table needs a caption');

it('accepts an aria-label or aria-labelledby instead of a caption', function (string $attribute): void {
    $dom = Html::render('<x-ui::table '.$attribute.' :columns="$columns"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);

    expect($dom->querySelector('table')->hasAttribute(explode('=', $attribute)[0]))->toBeTrue();
})->with(['aria-label="Fruit"', 'aria-labelledby="heading"']);

it('renders the caption visually hidden unless asked to show it', function (): void {
    $hidden = Html::render('<x-ui::table caption="Fruit" :columns="$columns"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);
    $visible = Html::render('<x-ui::table caption="Fruit" caption-visible :columns="$columns"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);

    expect($hidden->querySelector('caption')->textContent)->toBe('Fruit')
        ->and($hidden->querySelector('caption')->classList->contains('sr-only'))->toBeTrue()
        ->and($visible->querySelector('caption')->classList->contains('sr-only'))->toBeFalse();
});

it('renders column headers with scope col and the end alignment', function (): void {
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);

    $headers = $dom->querySelectorAll('thead th');

    expect($headers)->toHaveCount(3)
        ->and($headers[0]->getAttribute('scope'))->toBe('col')
        ->and(trim($headers[2]->textContent))->toBe('Price')
        ->and($headers[2]->classList->contains('text-end'))->toBeTrue()
        ->and($headers[0]->classList->contains('text-end'))->toBeFalse();
});

it('marks only the actively sorted column with aria-sort', function (string $direction, string $expected): void {
    $state = new RecordTableState(['name', 'price'], 'price', $direction);
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :state="$state"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS, 'state' => $state]);

    $headers = $dom->querySelectorAll('thead th');

    expect($headers[0]->hasAttribute('aria-sort'))->toBeFalse()
        ->and($headers[1]->hasAttribute('aria-sort'))->toBeFalse()
        ->and($headers[2]->getAttribute('aria-sort'))->toBe($expected);
})->with([['asc', 'ascending'], ['desc', 'descending']]);

it('renders buttons only for sortable columns, named by the column label', function (): void {
    $state = new RecordTableState(['name', 'price'], 'name', 'asc');
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :state="$state"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS, 'state' => $state]);

    $buttons = $dom->querySelectorAll('thead button');

    expect($buttons)->toHaveCount(2)
        ->and($buttons[0]->getAttribute('type'))->toBe('button')
        ->and($buttons[0]->getAttribute('wire:click'))->toBe("sortBy('name')")
        ->and(trim($buttons[0]->textContent))->toBe('Name')
        ->and($buttons[0]->hasAttribute('aria-label'))->toBeFalse()
        ->and($buttons[0]->querySelector('svg')->getAttribute('aria-hidden'))->toBe('true')
        ->and(trim($dom->querySelectorAll('thead th')[1]->textContent))->toBe('Colour')
        ->and($dom->querySelectorAll('thead th')[1]->querySelector('button'))->toBeNull();
});

it('renders no buttons without a state', function (): void {
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);

    expect($dom->querySelectorAll('thead button'))->toHaveCount(0);
});

it('escapes a hostile column key in the click handler', function (): void {
    $key = "x'\"><script>alert(1)</script>";
    $state = new RecordTableState([$key], null, 'asc');
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :state="$state"><tr><td>x</td></tr></x-ui::table>', ['columns' => [$key => 'Label'], 'state' => $state]);

    expect($dom->querySelector('script'))->toBeNull()
        ->and($dom->querySelector('thead button')->getAttribute('wire:click'))->toBe('sortBy('.Js::from($key)->toHtml().')')
        ->and($dom->querySelector('thead button')->getAttribute('wire:click'))->not->toContain('<script');
});

it('renders the empty state as one row spanning every column', function (): void {
    $records = paginatorOf(0);
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="$records"><tr class="row"><td>x</td></tr><x-slot:empty>Nothing here</x-slot:empty></x-ui::table>', ['columns' => TABLE_COLUMNS, 'records' => $records]);

    $cells = $dom->querySelectorAll('tbody td');

    expect($dom->querySelectorAll('tbody tr'))->toHaveCount(1)
        ->and($dom->querySelector('tr.row'))->toBeNull()
        ->and($cells)->toHaveCount(1)
        ->and($cells[0]->getAttribute('colspan'))->toBe('3')
        ->and(trim($cells[0]->textContent))->toBe('Nothing here');
});

it('falls back to a translatable empty message', function (): void {
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="[]"></x-ui::table>', ['columns' => TABLE_COLUMNS]);

    expect(trim($dom->querySelector('tbody td')->textContent))->toBe('No records found');
});

it('treats an empty collection as empty and a filled array as not empty', function (): void {
    $empty = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="collect()"></x-ui::table>', ['columns' => TABLE_COLUMNS]);
    $filled = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="[1]"><tr class="row"><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);

    expect($empty->querySelector('tbody td[colspan]'))->not->toBeNull()
        ->and($filled->querySelector('tbody td[colspan]'))->toBeNull()
        ->and($filled->querySelector('tr.row'))->not->toBeNull();
});

it('wires aria-busy and the loading message to Livewire requests', function (): void {
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);

    $status = $dom->querySelector('[role=status]');

    expect($dom->querySelector('table')->hasAttribute('wire:loading.attr'))->toBeTrue()
        ->and($dom->querySelector('table')->getAttribute('wire:loading.attr'))->toBe('aria-busy')
        ->and($dom->querySelector('table')->hasAttribute('aria-busy'))->toBeFalse()
        ->and($status->querySelector('[wire\:loading]'))->not->toBeNull()
        ->and(trim($status->querySelector('[wire\:loading]')->textContent))->toBe('Loading…');
});

it('announces the visible range of a paginator', function (): void {
    $records = paginatorOf(42, 10, 2);
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="$records"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS, 'records' => $records]);

    expect($dom->querySelector('[role=status]')->textContent)->toContain('Showing 11 to 20 of 42 results');
});

it('renders pagination only when there is more than one page', function (): void {
    $single = paginatorOf(10);
    $many = paginatorOf(42, 10, 2);

    $one = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="$records"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS, 'records' => $single]);
    $several = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="$records"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS, 'records' => $many]);

    $nav = $several->querySelector('nav');

    expect($one->querySelector('nav'))->toBeNull()
        ->and($nav->getAttribute('aria-label'))->toBe('Pagination')
        ->and($nav->querySelector('[aria-current=page]')->textContent)->toContain('2')
        ->and($nav->querySelectorAll('button'))->toHaveCount(7)
        ->and($nav->querySelector('button[wire\:click="gotoPage(3, \'page\')"]'))->not->toBeNull();
});

it('renders the toolbar slot above the table and merges attributes onto the table', function (): void {
    $dom = Html::render('<x-ui::table caption="Fruit" id="fruit" :columns="$columns"><x-slot:toolbar><input id="search"></x-slot:toolbar><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);

    expect($dom->querySelector('table')->getAttribute('id'))->toBe('fruit')
        ->and($dom->querySelector('#search'))->not->toBeNull()
        ->and($dom->querySelector('#search')->compareDocumentPosition($dom->querySelector('table')) & 4)->toBe(4);
});

it('keeps the current page and the ends of the pager focusable', function (): void {
    $first = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="$records"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS, 'records' => paginatorOf(42)]);
    $last = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="$records"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS, 'records' => paginatorOf(42, 10, 5)]);

    $current = $first->querySelector('nav [aria-current=page]');
    $previous = $first->querySelectorAll('nav button')[0];
    $next = $last->querySelectorAll('nav button')[6];

    expect($current->tagName)->toBe('BUTTON')
        ->and($previous->hasAttribute('disabled'))->toBeFalse()
        ->and($previous->getAttribute('aria-disabled'))->toBe('true')
        ->and($previous->hasAttribute('wire:click'))->toBeFalse()
        ->and($next->hasAttribute('disabled'))->toBeFalse()
        ->and($next->getAttribute('aria-disabled'))->toBe('true')
        ->and($next->hasAttribute('wire:click'))->toBeFalse()
        ->and($first->querySelector('nav button:last-child')->getAttribute('wire:click'))->toBe("nextPage('page')");
});

it('scopes the busy state, spinner and loading message to the table state', function (): void {
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="$columns" :records="[1]"><tr><td>x</td></tr></x-ui::table>', ['columns' => TABLE_COLUMNS]);

    $targets = array_map(fn ($element): string => $element->getAttribute('wire:target'), iterator_to_array($dom->querySelectorAll('[wire\:loading], [wire\:loading\.attr], [wire\:loading\.delay\.flex]')));

    expect($targets)->toHaveCount(3)
        ->and(array_unique($targets))->toHaveCount(1)
        ->and(explode(',', $targets[0]))->toContain('sortBy', 'search', 'filters', 'perPage', 'gotoPage', 'nextPage', 'previousPage', 'setPage', 'resetFilters');
});
