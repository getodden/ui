<?php

declare(strict_types=1);

use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Odden\Ui\Tests\Support\CoexistingRecordTableComponent;
use Odden\Ui\Tests\Support\CustomTiebreakRecordTableComponent;
use Odden\Ui\Tests\Support\RecordTableComponent;
use Odden\Ui\Tests\Support\RecordTableFixtures;
use Odden\Ui\Tests\Support\UnsortedRecordTableComponent;
use Workbench\App\Models\Fruit;

beforeEach(function (): void {
    RecordTableFixtures::install();
});

function fruitNames(Testable $component): array
{
    preg_match_all('/<li>(.*?)<\/li>/', $component->html(), $matches);

    return $matches[1];
}

function tableReport(Testable $component): array
{
    return json_decode($component->instance()->report(), true, flags: JSON_THROW_ON_ERROR);
}

it('applies the default sort, per page and a stable first page', function (): void {
    $component = Livewire::test(RecordTableComponent::class);

    expect(fruitNames($component))->toBe(['Apple', 'Apricot', 'Avocado', 'Banana', 'Blackberry', 'Blueberry', 'Cantaloupe', 'Cherry', 'Clementine', 'Cranberry'])
        ->and(tableReport($component))->toMatchArray(['sort' => 'name', 'direction' => 'asc', 'perPage' => 10]);

    $component->assertSee('total=30');
});

it('sorts by a column, toggles the direction on the same key and starts a new key ascending', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->call('sortBy', 'price');

    expect(fruitNames($component)[0])->toBe('Lime')
        ->and(tableReport($component))->toMatchArray(['sort' => 'price', 'direction' => 'asc']);

    $component->call('sortBy', 'price');

    expect(fruitNames($component)[0])->toBe('Dragonfruit')
        ->and(tableReport($component))->toMatchArray(['sort' => 'price', 'direction' => 'desc']);

    $component->call('sortBy', 'name');

    expect(fruitNames($component)[0])->toBe('Apple')
        ->and(tableReport($component))->toMatchArray(['sort' => 'name', 'direction' => 'asc']);
});

it('toggles the default sort on its first click', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->call('sortBy', 'name');

    expect(fruitNames($component)[0])->toBe('Raspberry')
        ->and(tableReport($component))->toMatchArray(['sort' => 'name', 'direction' => 'desc']);
});

it('ignores a sort key that is not allow-listed', function (string $key): void {
    $component = Livewire::test(RecordTableComponent::class)->call('sortBy', 'price')->call('sortBy', $key);

    expect(tableReport($component))->toMatchArray(['sort' => 'price', 'direction' => 'asc'])
        ->and($component->get('sort'))->toBe('price');
})->with(['password', 'id; drop table fruits', '', 'NAME', 'created_at']);

it('sorts with a custom closure', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->call('sortBy', 'colour');

    expect(fruitNames($component)[0])->toBe('Date');
});

it('breaks ties on the model key', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('perPage', 50)->call('sortBy', 'colourPlain');

    $green = array_values(array_filter(fruitNames($component), fn (string $name): bool => in_array($name, ['Avocado', 'Grape', 'Guava', 'Kiwi', 'Lime', 'Melon', 'Pear'], true)));

    expect($green)->toBe(['Avocado', 'Grape', 'Guava', 'Kiwi', 'Lime', 'Melon', 'Pear']);
});

it('searches across columns case-insensitively', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('search', 'PURPLE');

    expect(fruitNames($component))->toBe(['Blackberry', 'Blueberry', 'Fig', 'Plum']);

    $component->set('search', 'berry');

    expect(fruitNames($component))->toBe(['Blackberry', 'Blueberry', 'Cranberry', 'Raspberry']);
});

it('trims the search term and caps its length', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('search', "  Kiwi \n");

    expect(fruitNames($component))->toBe(['Kiwi']);

    $component->set('search', str_repeat('a', 500));

    expect(mb_strlen(tableReport($component)['search']))->toBe(100);
});

it('treats like wildcards and the escape character in the search term literally', function (string $term): void {
    Livewire::test(RecordTableComponent::class)->set('search', $term)->assertSee('total=0');
})->with(['%', '_', 'A_ple', 'App%', '!', '\\', 'a!']);

it('applies an allow-listed filter and ignores the rest', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('filters', ['colour' => 'purple', 'price' => '70', 'unknown' => 'x']);

    expect(fruitNames($component))->toBe(['Blackberry', 'Blueberry', 'Fig', 'Plum'])
        ->and(tableReport($component)['filters'])->toBe(['colour' => 'purple']);
});

it('skips an empty filter value', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('filters', ['colour' => '']);

    expect(tableReport($component)['filters'])->toBe([])
        ->and($component->html())->toContain('total=30');
});

it('combines search, filter and sort', function (): void {
    $component = Livewire::test(RecordTableComponent::class)
        ->set('filters', ['colour' => 'orange'])
        ->set('search', 'a')
        ->call('sortBy', 'price');

    expect(fruitNames($component))->toBe(['Orange', 'Clementine', 'Grapefruit', 'Apricot', 'Peach', 'Nectarine', 'Mango', 'Cantaloupe', 'Papaya']);
});

it('falls back to the first per-page option for a value outside the list', function (int $value): void {
    $component = Livewire::test(RecordTableComponent::class)->set('perPage', $value);

    expect(tableReport($component)['perPage'])->toBe(10)
        ->and(count(fruitNames($component)))->toBe(10);
})->with([100000, -1, 0, 7]);

it('accepts a per-page value from the list', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('perPage', 25);

    expect(count(fruitNames($component)))->toBe(25);
});

it('returns to the first page when search, filters, sort or per page change', function (string $property, mixed $value): void {
    $component = Livewire::test(RecordTableComponent::class)->call('gotoPage', 2);

    expect($component->html())->toContain('page=2');

    $component->set($property, $value);

    expect($component->html())->toContain('page=1');
})->with([
    ['search', 'a'],
    ['filters', ['colour' => 'red']],
    ['filters.colour', 'red'],
    ['perPage', 25],
    ['sort', 'price'],
    ['direction', 'desc'],
]);

it('returns to the first page when a sort or reset action runs', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->call('gotoPage', 2)->call('sortBy', 'price');

    expect($component->html())->toContain('page=1');

    $component->call('gotoPage', 2)->call('resetFilters');

    expect($component->html())->toContain('page=1');
});

it('clears filters', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('filters', ['colour' => 'red'])->call('resetFilters');

    expect($component->get('filters'))->toBe([])
        ->and($component->html())->toContain('total=30');
});

it('keeps the consumer own updated hook working', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->call('gotoPage', 2)->set('search', 'a');

    expect($component->get('searchUpdates'))->toBe(1)
        ->and($component->html())->toContain('page=1');
});

it('works when the consumer also uses WithPagination itself', function (): void {
    $component = Livewire::test(CoexistingRecordTableComponent::class)->call('nextPage')->call('sortBy', 'price');

    expect(fruitNames($component)[0])->toBe('Lime')
        ->and($component->html())->toContain('page=1');
});

it('reads valid state from the query string', function (): void {
    $component = Livewire::withQueryParams(['search' => 'berry', 'sort' => 'price', 'direction' => 'desc', 'filters' => ['colour' => 'red'], 'perPage' => '25'])
        ->test(RecordTableComponent::class);

    expect(fruitNames($component))->toBe(['Raspberry', 'Cranberry'])
        ->and(tableReport($component))->toMatchArray(['sort' => 'price', 'direction' => 'desc', 'perPage' => 25, 'search' => 'berry']);
});

it('keeps a clean url when the state is the default', function (): void {
    Livewire::test(RecordTableComponent::class)
        ->assertSet('search', '')
        ->assertSet('sort', '')
        ->assertSet('filters', [])
        ->assertSet('perPage', 10);
});

it('survives hostile query strings', function (array $query): void {
    $component = Livewire::withQueryParams($query)->test(RecordTableComponent::class);

    $report = tableReport($component);

    expect($report['sort'])->toBe('name')
        ->and($report['direction'])->toBeIn(['asc', 'desc'])
        ->and($report['filters'])->toBe([])
        ->and($report['perPage'])->toBe(10)
        ->and(count(fruitNames($component)))->toBeLessThanOrEqual(10);
})->with([
    'sort of a column that is not allowed' => [['sort' => 'password']],
    'sort array' => [['sort' => ['x']]],
    'sort sql' => [['sort' => 'id; drop table fruits']],
    'direction nonsense' => [['direction' => 'sideways']],
    'direction array' => [['direction' => ['desc']]],
    'filters string' => [['filters' => 'abc']],
    'filters unknown key' => [['filters' => ['unknown' => 'x']]],
    'filters nested array' => [['filters' => ['colour' => ['x']]]],
    'filters json' => [['filters' => '{"price":"1"}']],
    'perPage huge' => [['perPage' => '100000']],
    'perPage negative' => [['perPage' => '-1']],
    'perPage text' => [['perPage' => 'abc']],
    'perPage array' => [['perPage' => ['5']]],
    'perPage float' => [['perPage' => '1e9']],
    'search array' => [['search' => ['x']]],
    'page text' => [['page' => 'abc']],
]);

it('keeps a hostile search string harmless', function (): void {
    $component = Livewire::withQueryParams(['search' => "x' or 1=1 --"])->test(RecordTableComponent::class);

    $component->assertSee('total=0');
});

it('survives hostile values set from the browser', function (string $property, mixed $value): void {
    $component = Livewire::test(RecordTableComponent::class)->set($property, $value);

    $report = tableReport($component);

    expect($report['sort'])->toBe('name')
        ->and($report['filters'])->toBe([])
        ->and($report['perPage'])->toBe(10)
        ->and($report['search'])->toBeString()
        ->and(fruitNames($component))->not->toBe([]);
})->with([
    ['sort', 'id; drop table'],
    ['sort', ['x']],
    ['direction', 'sideways'],
    ['direction', ['asc']],
    ['filters', 'abc'],
    ['filters', ['colour' => ['x']]],
    ['filters', ['colour' => null]],
    ['perPage', '100000'],
    ['perPage', '-1'],
    ['perPage', ['x']],
    ['perPage', 'abc'],
    ['perPage', true],
    ['search', ['x']],
]);

it('accepts a per-page value sent as a string', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('perPage', '25');

    expect(count(fruitNames($component)))->toBe(25);
});

it('keeps a hostile sort from the browser out of the query', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('sort', 'id; drop table')->set('direction', 'desc; --');

    expect(tableReport($component))->toMatchArray(['sort' => 'name'])
        ->and(tableReport($component)['direction'])->toBe('asc')
        ->and(fruitNames($component)[0])->toBe('Apple');
});

it('exposes the sort state for the table header', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->call('sortBy', 'price')->call('sortBy', 'price');

    expect(tableReport($component)['state'])->toBe([
        'sortable' => ['name', 'price', 'colourPlain', 'colour'],
        'sort' => 'price',
        'direction' => 'desc',
        'tabs' => [],
        'tab' => null,
        'selectedCount' => 0,
        'bulkActions' => [],
        'dangerousBulkActions' => [],
    ]);
});

it('searches non-ASCII text on any database', function (string $term): void {
    Fruit::query()->create(['name' => 'Émile', 'colour' => 'red', 'price' => 1]);

    $component = Livewire::test(RecordTableComponent::class)->set('search', $term);

    expect(fruitNames($component))->toBe(['Émile']);
})->with(['Émile', 'mile']);

it('lowers both sides of the search in the database', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->set('search', 'Ab');

    expect($component->instance()->orderedSql())->toContain("lower(\"name\") like lower('%Ab%') escape '!'");
});

it('replaces the consumer order with the chosen sort and breaks ties on the key', function (): void {
    $sql = Livewire::test(RecordTableComponent::class)->call('sortBy', 'price')->instance()->orderedSql();

    expect($sql)->toContain('order by "price" asc, "fruits"."id" asc')->not->toContain('desc');
});

it('keeps the consumer order and appends the key tiebreak when nothing is sorted', function (): void {
    $sql = Livewire::test(UnsortedRecordTableComponent::class)->instance()->orderedSql();

    expect($sql)->toContain('order by "price" desc, "fruits"."id" asc');
});

it('pages deterministically without a sort', function (): void {
    $component = Livewire::test(UnsortedRecordTableComponent::class)->set('perPage', 10)->call('gotoPage', 2);

    expect(fruitNames($component)[0])->toBe('Date');
});

it('clamps a page past the end to the last page', function (): void {
    $component = Livewire::withQueryParams(['page' => '99999'])->test(RecordTableComponent::class);

    expect(fruitNames($component))->toBe(['Mango', 'Melon', 'Nectarine', 'Orange', 'Papaya', 'Peach', 'Pear', 'Pineapple', 'Plum', 'Raspberry'])->and($component->html())->toContain('page=3');
});

it('clamps a page past the end after the results shrink', function (): void {
    $component = Livewire::test(RecordTableComponent::class)->call('gotoPage', 3);

    $component->set('filters.colour', 'purple');

    expect(fruitNames($component))->toBe(['Blackberry', 'Blueberry', 'Fig', 'Plum']);
});

it('shows a page past the end of no records as empty', function (): void {
    $component = Livewire::withQueryParams(['page' => '5'])->test(RecordTableComponent::class)->set('search', 'zzzz');

    expect(fruitNames($component))->toBe([])->and($component->html())->toContain('total=0');
});

it('survives invalid UTF-8 in any state property from the url', function (string $property, mixed $value): void {
    $component = Livewire::withQueryParams([$property => $value])->test(RecordTableComponent::class);

    expect(mb_check_encoding(json_encode($component->snapshot, JSON_INVALID_UTF8_SUBSTITUTE) ?: '', 'UTF-8'))->toBeTrue()
        ->and($component->instance())->not->toBeNull();
})->with([
    ['search', "\xff"],
    ['sort', "\xff"],
    ['direction', "\xff"],
    ['perPage', "\xff"],
    ['filters', ["\xff" => "\xfe"]],
    ['filters', ['colour' => "\xff"]],
]);

it('skips the key tiebreak for grouped queries', function (): void {
    $component = Livewire::test(RecordTableComponent::class);

    expect($component->instance()->groupedSql())->not->toContain('"fruits"."id"')
        ->and($component->instance()->orderedSql())->toContain('"fruits"."id" asc');
});

it('lets the consumer choose the tiebreak column', function (): void {
    $sql = Livewire::test(CustomTiebreakRecordTableComponent::class)->instance()->orderedSql();

    expect($sql)->toContain('order by "name" asc, "name" asc');
});
