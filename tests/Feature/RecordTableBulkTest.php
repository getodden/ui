<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Odden\Ui\Livewire\Concerns\WithRecordTable;
use Odden\Ui\Support\RecordTableReads;
use Odden\Ui\Tests\Support\BulkRecordTableComponent;
use Odden\Ui\Tests\Support\Html;
use Odden\Ui\Tests\Support\OwnConfirmingFlagRecordTableComponent;
use Odden\Ui\Tests\Support\RecordTableFixtures;
use Odden\Ui\Tests\Support\WideRecordTableComponent;
use Workbench\App\Models\Fruit;

beforeEach(function (): void {
    RecordTableFixtures::install();
});

function bulkReport(Testable $component): array
{
    return json_decode($component->instance()->report(), true, flags: JSON_THROW_ON_ERROR);
}

function fruitIds(string ...$names): array
{
    return Fruit::query()->whereIn('name', $names)->orderBy('id')->pluck('id')->map(strval(...))->all();
}

it('defaults to the first tab and applies its scope', function (): void {
    $component = Livewire::test(BulkRecordTableComponent::class);

    expect(bulkReport($component)['tab'])->toBe('all')
        ->and($component->get('tab'))->toBe('');
    $component->assertSee('Apple');
});

it('applies a tab scope and keeps it in the url', function (): void {
    $component = Livewire::test(BulkRecordTableComponent::class)->call('setTab', 'red');

    expect(bulkReport($component)['tab'])->toBe('red')
        ->and($component->get('tab'))->toBe('red');
    $component->assertSee('Raspberry')->assertDontSee('Banana');
});

it('stores the default tab as an empty url value', function (): void {
    $component = Livewire::test(BulkRecordTableComponent::class)->call('setTab', 'red')->call('setTab', 'all');

    expect($component->get('tab'))->toBe('');
});

it('ignores an unknown tab value from a call, the url or a direct set', function (mixed $value): void {
    $component = Livewire::test(BulkRecordTableComponent::class)->call('setTab', 'red')->call('setTab', 'nope');

    expect($component->get('tab'))->toBe('red');

    $component->set('tab', $value);

    expect(bulkReport($component)['tab'])->toBe('all');
})->with(['nope', 'RED', [['red']], 7, 'red; drop table fruits']);

it('reads the tab from the url', function (): void {
    $component = Livewire::withQueryParams(['tab' => 'pricey'])->test(BulkRecordTableComponent::class);

    expect(bulkReport($component)['tab'])->toBe('pricey');
    $component->assertSee('Date')->assertDontSee('Apple');
});

it('marks the active tab with aria-current and renders every tab as a button', function (): void {
    $dom = Html::parse(Livewire::test(BulkRecordTableComponent::class)->call('setTab', 'red')->html());

    $buttons = $dom->querySelectorAll('nav button');

    expect($buttons)->toHaveCount(4)
        ->and($buttons[0]->hasAttribute('aria-current'))->toBeFalse()
        ->and($buttons[1]->getAttribute('aria-current'))->toBe('true');
});

it('selects rows and reports the count', function (): void {
    $ids = fruitIds('Apple', 'Apricot');
    $component = Livewire::test(BulkRecordTableComponent::class)->set('selected', $ids);

    expect(bulkReport($component)['selected'])->toBe($ids)
        ->and(bulkReport($component)['state']['selectedCount'])->toBe(2);
    $component->assertSee('2 rows selected');
});

it('hands the selected keys to a declared bulk action and clears the selection', function (): void {
    $ids = fruitIds('Apple', 'Apricot');
    $component = Livewire::test(BulkRecordTableComponent::class)->set('selected', $ids)->call('runBulkAction', 'recordSelection');

    expect($component->get('received'))->toBe($ids)
        ->and($component->get('selected'))->toBe([]);
});

it('refuses a method that is not a declared bulk action', function (string $method): void {
    $component = Livewire::test(BulkRecordTableComponent::class)->set('selected', fruitIds('Apple'))->call('runBulkAction', $method);

    expect($component->get('received'))->toBe([])
        ->and($component->get('selected'))->toBe(fruitIds('Apple'));
})->with(['unlistedAction', 'report', 'mount', 'nope', '']);

it('does not run a bulk action with an empty selection', function (): void {
    expect(Livewire::test(BulkRecordTableComponent::class)->call('runBulkAction', 'recordSelection')->get('received'))->toBe([]);
});

it('keeps the selection when a bulk action throws', function (): void {
    $ids = fruitIds('Apple');
    $component = Livewire::test(BulkRecordTableComponent::class)->set('selected', $ids);

    expect(fn () => $component->call('runBulkAction', 'refuseSelection'))->toThrow(InvalidArgumentException::class);
    expect($component->get('selected'))->toBe($ids);
});

it('cleans untrusted selections before a bulk action sees them', function (): void {
    $component = Livewire::test(BulkRecordTableComponent::class)
        ->set('selected', ['1', 1, '1', '', null, ['2'], 2.0, new stdClass, str_repeat('x', 300), "bad\xff"])
        ->call('runBulkAction', 'recordSelection');

    expect($component->get('received'))->toBe(['1', '2']);
});

it('ignores a selection that is not a list', function (mixed $value): void {
    expect(bulkReport(Livewire::test(BulkRecordTableComponent::class)->set('selected', $value))['selected'])->toBe([]);
})->with(['text', 5, null]);

it('caps the selection at the declared maximum', function (): void {
    $ids = fruitIds('Apple', 'Apricot', 'Avocado');
    $component = Livewire::test(BulkRecordTableComponent::class)->set('maximum', 2)->set('selected', $ids)->call('runBulkAction', 'recordSelection');

    expect($component->get('received'))->toBe(array_slice($ids, 0, 2));
});

it('clears the selection on page, filter, search, sort, per-page and tab changes', function (string $change, array $arguments): void {
    $component = Livewire::test(BulkRecordTableComponent::class)->set('selected', fruitIds('Apple'));

    $component->call($change, ...$arguments);

    expect($component->get('selected'))->toBe([]);
})->with([
    'next page' => ['nextPage', []],
    'goto page' => ['gotoPage', [2]],
    'tab' => ['setTab', ['red']],
    'sort' => ['sortBy', ['name']],
    'filters' => ['resetFilters', []],
]);

it('clears the selection when a table setting is set directly', function (string $property, mixed $value): void {
    $component = Livewire::test(BulkRecordTableComponent::class)->set('selected', fruitIds('Apple'))->set($property, $value);

    expect($component->get('selected'))->toBe([]);
})->with([['search', 'ap'], ['perPage', 25], ['tab', 'red'], ['filters.colour', 'red']]);

it('keeps the selection when only it changes', function (): void {
    $component = Livewire::test(BulkRecordTableComponent::class)->set('selected', fruitIds('Apple'))->set('selected', fruitIds('Apple', 'Apricot'));

    expect($component->get('selected'))->toBe(fruitIds('Apple', 'Apricot'));
});

it('clears the selection on request', function (): void {
    expect(Livewire::test(BulkRecordTableComponent::class)->set('selected', fruitIds('Apple'))->call('clearSelection')->get('selected'))->toBe([]);
});

it('renders a labelled checkbox per row, a select-all checkbox and a count for assistive technology', function (): void {
    $dom = Html::parse(Livewire::test(BulkRecordTableComponent::class)->set('selected', fruitIds('Apple'))->html());

    $rowBoxes = $dom->querySelectorAll('tbody input[type=checkbox]');
    $all = $dom->querySelector('thead input[type=checkbox]');

    expect($rowBoxes)->toHaveCount(10)
        ->and($rowBoxes[0]->getAttribute('aria-label'))->toBe('Select Apple')
        ->and($all->getAttribute('aria-label'))->toBe('Select all rows on this page')
        ->and(json_decode($all->getAttribute('data-keys'), true))->toHaveCount(10)
        ->and(trim($dom->querySelector('[role=status]')->textContent))->toContain('1 row selected');
});

it('shows the bulk bar only while rows are selected', function (): void {
    $none = Html::parse(Livewire::test(BulkRecordTableComponent::class)->html());
    $some = Html::parse(Livewire::test(BulkRecordTableComponent::class)->set('selected', fruitIds('Apple'))->html());

    $group = $some->querySelector('[role=group]');

    expect($none->querySelector('[role=group]'))->toBeNull()
        ->and($group)->not->toBeNull()
        ->and($group->querySelectorAll('button'))->toHaveCount(4)
        ->and($group->textContent)->toContain('Record');
});

it('renders a row menu with a labelled trigger and menu items', function (): void {
    $dom = Html::parse(Livewire::test(BulkRecordTableComponent::class)->html());

    $trigger = $dom->querySelector('tbody tr button[aria-haspopup=menu]');
    $menu = $dom->querySelector('tbody tr [role=menu]');
    $items = $menu->querySelectorAll('[role=menuitem]');

    expect($trigger->getAttribute('aria-label'))->toBe('Actions for Apple')
        ->and($menu->getAttribute('aria-label'))->toBe('Actions for Apple')
        ->and($items)->toHaveCount(3)
        ->and($items[0]->getAttribute('tabindex'))->toBe('-1')
        ->and($items[2]->classList->contains('text-rose-700'))->toBeTrue();
});

it('adds the selection and actions columns to the empty row span', function (): void {
    $dom = Html::parse(Livewire::test(BulkRecordTableComponent::class)->call('setTab', 'pricey')->set('selected', [])->html());

    expect($dom->querySelectorAll('thead th'))->toHaveCount(3);

    $empty = Html::render('<x-ui::table caption="Fruit" :columns="[\'a\' => \'A\']" :records="[]" :selectable-keys="[]" row-actions />');

    expect($empty->querySelector('tbody td')->getAttribute('colspan'))->toBe('3');
});

it('requires labels and values on the row components', function (string $template): void {
    Html::render($template);
})->with([
    '<x-ui::table.select value="1" />',
    '<x-ui::table.select label="Select" />',
    '<x-ui::table.actions>x</x-ui::table.actions>',
])->throws(ViewException::class);

it('lists only methods that exist on the trait as pure reads and never the bulk dispatcher', function (): void {
    foreach (RecordTableReads::METHODS as $name) {
        expect((new ReflectionMethod(BulkRecordTableComponent::class, $name))->isPublic())->toBeTrue();
    }

    expect(RecordTableReads::METHODS)->not->toContain('runBulkAction')
        ->and((new ReflectionMethod(WithRecordTable::class, 'runBulkAction'))->isPublic())->toBeTrue();
});

it('keeps the selection while a bulk action waits for confirmation and clears it once confirmed', function (): void {
    $ids = fruitIds('Apple', 'Apricot');
    $component = Livewire::test(BulkRecordTableComponent::class)->set('selected', $ids)->call('runBulkAction', 'askToRemove');

    expect($component->get('confirming'))->toBeTrue()
        ->and($component->get('selected'))->toBe($ids);

    $component->call('cancelConfirmation');

    expect($component->get('selected'))->toBe($ids);

    $component->call('runBulkAction', 'askToRemove')->call('runConfirmation');

    expect($component->get('received'))->toBe($ids)
        ->and($component->get('selected'))->toBe([]);
});

it('refuses to run a bulk action that is public', function (): void {
    $component = Livewire::test(WideRecordTableComponent::class)->set('selected', ['1']);

    expect(fn () => $component->call('runBulkAction', 'exposedSelection'))->toThrow(LogicException::class);
    expect($component->get('received'))->toBe([]);
});

it('does not cut a full page selection when the page size is above the default cap', function (): void {
    $keys = array_map(strval(...), range(1, 250));
    $component = Livewire::test(WideRecordTableComponent::class)->set('selected', $keys);

    expect($component->get('selected'))->toBe($keys);
});

it('reads the tabs no more than twice per render', function (): void {
    $component = Livewire::test(BulkRecordTableComponent::class);

    expect($component->get('tabReads'))->toBeLessThanOrEqual(2);
});

it('gives names with special characters a clean accessible name', function (): void {
    $name = "Tom & Jerry's <b>";
    $dom = Html::render('<x-ui::table.select value="1" :label="\'Select \'.$name" /><x-ui::table.actions :label="\'Actions for \'.$name">x</x-ui::table.actions>', ['name' => $name]);

    expect($dom->querySelector('input')->getAttribute('aria-label'))->toBe('Select '.$name)
        ->and($dom->querySelector('button[aria-haspopup=menu]')->getAttribute('aria-label'))->toBe('Actions for '.$name);
});

it('accepts a collection of selectable keys', function (): void {
    $dom = Html::render('<x-ui::table caption="Fruit" :columns="[\'a\' => \'A\']" :records="[]" :selectable-keys="collect([1, 2])" />');

    expect(json_decode($dom->querySelector('thead input[type=checkbox]')->getAttribute('data-keys'), true))->toBe(['1', '2']);
});

it('lets the bulk buttons restore focus to the page checkbox once the bar disappears', function (): void {
    $dom = Html::parse(Livewire::test(BulkRecordTableComponent::class)->set('selected', fruitIds('Apple'))->html());

    expect($dom->querySelector('thead input[type=checkbox]')->hasAttribute('data-select-page'))->toBeTrue()
        ->and($dom->querySelector('[role=group] button')->getAttribute('x-on:click'))->toContain('[data-select-page]')
        ->and($dom->querySelector('div[data-record-table]')->hasAttribute('x-data'))->toBeFalse();
});

it('drops selected rows that have left the current page', function (): void {
    $ids = fruitIds('Apple', 'Apricot');
    $component = Livewire::test(BulkRecordTableComponent::class)->set('selected', $ids);

    Fruit::query()->whereKey($ids[0])->delete();
    $component->call('$refresh');

    expect($component->get('selected'))->toBe([$ids[1]]);

    $component->call('runBulkAction', 'recordSelection');

    expect($component->get('received'))->toBe([$ids[1]]);
});

it('drops selected rows that the active tab does not show', function (): void {
    $ids = fruitIds('Apple', 'Banana');
    $component = Livewire::test(BulkRecordTableComponent::class)->call('setTab', 'red')->set('selected', $ids);

    expect($component->get('selected'))->toBe([$ids[0]]);
});

it('groups a tab scope so its or conditions cannot escape the search', function (): void {
    $component = Livewire::test(BulkRecordTableComponent::class)->call('setTab', 'loose')->set('search', 'Apple');

    $component->assertSee('Apple')->assertDontSee('Raspberry')->assertDontSee('Date');
});

it('keeps the selection after a bulk action only when the component uses WithConfirmation', function (): void {
    $ids = fruitIds('Apple');
    $component = Livewire::test(OwnConfirmingFlagRecordTableComponent::class)->set('selected', $ids)->call('runBulkAction', 'recordSelection');

    expect($component->get('received'))->toBe($ids)
        ->and($component->get('selected'))->toBe([]);
});

it('clears the selection on a page change even when the host defines updatedPaginators', function (): void {
    $component = Livewire::test(OwnConfirmingFlagRecordTableComponent::class)->set('selected', fruitIds('Apple'))->call('nextPage');

    expect($component->get('selected'))->toBe([])
        ->and($component->get('received'))->toBe(['scrolled']);
});

it('styles dangerous bulk actions as danger and renders link row actions as anchors', function (): void {
    $dom = Html::parse(Livewire::test(BulkRecordTableComponent::class)->set('selected', fruitIds('Apple'))->html());

    $buttons = $dom->querySelectorAll('[role=group] button');
    $link = $dom->querySelector('tbody tr a[role=menuitem]');

    expect($buttons[2]->textContent)->toContain('Remove')
        ->and($buttons[2]->className)->not->toBe($buttons[0]->className)
        ->and($link->getAttribute('href'))->toStartWith('/fruit/');
});
