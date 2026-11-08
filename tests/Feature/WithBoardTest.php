<?php

declare(strict_types=1);

use Livewire\Livewire;
use Odden\Ui\Tests\Support\BoardComponent;
use Odden\Ui\Tests\Support\Html;
use Odden\Ui\Tests\Support\RecordTableFixtures;

beforeEach(function (): void {
    RecordTableFixtures::install();
});

it('hands the card, column and position to the host write method', function (): void {
    $component = Livewire::test(BoardComponent::class)->call('moveCard', 3, 'green', 1)->assertReturned(true)->call('moveCard', '4', 'red', '0')->assertReturned(true);

    expect($component->get('moves'))->toBe([
        ['card' => '3', 'column' => 'green', 'position' => 1, 'after' => null, 'toEnd' => false],
        ['card' => '4', 'column' => 'red', 'position' => 0, 'after' => null, 'toEnd' => false],
    ]);
});

it('returns true when the host accepts and false when it refuses', function (string $outcome, bool $expected): void {
    Livewire::test(BoardComponent::class)->set('outcome', $outcome)->call('moveCard', '1', 'red', 0)->assertReturned($expected);
})->with([['accept', true], ['refuse', false], ['forbid', false], ['abort', false], ['invalid', false]]);

it('never calls the host for a column that is not on the board', function (mixed $column): void {
    $component = Livewire::test(BoardComponent::class)->call('moveCard', '1', $column, 0)->assertReturned(false);

    expect($component->get('moves'))->toBe([]);
})->with([['purple'], [''], ['RED'], ['red '], [null], [['red']], [1.5], [true]]);

it('never calls the host for a card id or position it cannot trust', function (mixed $card, mixed $position): void {
    $component = Livewire::test(BoardComponent::class)->call('moveCard', $card, 'red', $position)->assertReturned(false);

    expect($component->get('moves'))->toBe([]);
})->with([
    'array id' => [['1'], 0],
    'null id' => [null, 0],
    'empty id' => ['', 0],
    'oversized id' => [str_repeat('9', 101), 0],
    'invalid utf-8 id' => ["\xB1\x31", 0],
    'text position' => ['1', 'first'],
    'float position' => ['1', 1.5],
    'array position' => ['1', [0]],
    'huge digit position' => ['1', str_repeat('9', 40)],
]);

it('hands the neighbour the card now sits below to the host', function (): void {
    $component = Livewire::test(BoardComponent::class)->call('moveCard', '3', 'green', 1, '7')->assertReturned(true)->call('moveCard', '3', 'green', 0, 7)->assertReturned(true);

    expect($component->get('moves'))->toBe([
        ['card' => '3', 'column' => 'green', 'position' => 1, 'after' => '7', 'toEnd' => false],
        ['card' => '3', 'column' => 'green', 'position' => 0, 'after' => '7', 'toEnd' => false],
    ]);
});

it('never calls the host for a neighbour id it cannot trust', function (mixed $after): void {
    $component = Livewire::test(BoardComponent::class)->call('moveCard', '1', 'red', 0, $after)->assertReturned(false);

    expect($component->get('moves'))->toBe([]);
})->with([[['7']], [''], [str_repeat('9', 101)], ["\xB1\x31"], [1.5]]);

it('clamps a negative or absurd position before it reaches the host', function (int $position, int $expected): void {
    $component = Livewire::test(BoardComponent::class)->call('moveCard', '1', 'red', $position);

    expect($component->get('moves')[0]['position'])->toBe($expected);
})->with([[PHP_INT_MAX, 100000], [-5, 0]]);

it('shows the first page of every column and a show more button only where more cards exist', function (): void {
    $dom = Html::render('{!! $html !!}', ['html' => Livewire::test(BoardComponent::class)->html()]);

    $columns = $dom->querySelectorAll('[data-board-column]');
    $red = $columns[0];

    expect($columns)->toHaveCount(3)
        ->and($red->querySelectorAll('[data-board-card]'))->toHaveCount(2)
        ->and(trim($red->querySelector('[data-board-count]')->textContent))->toBe('6')
        ->and(trim($red->querySelector('[data-board-total]')->textContent))->toBe('6 items')
        ->and($red->querySelector('button[wire\\:click]'))->not->toBeNull()
        ->and($columns[2]->querySelectorAll('[data-board-card]'))->toHaveCount(2);
});

it('loads another page of a column and caps the limit', function (): void {
    $component = Livewire::test(BoardComponent::class)->call('loadMoreCards', 'red');

    expect($component->instance()->limitOf('red'))->toBe(4)
        ->and($component->instance()->limitOf('green'))->toBe(2)
        ->and(substr_count($component->html(), 'data-board-card='))->toBe(4 + 2 + 2);

    $component->call('loadMoreCards', 'red')->call('loadMoreCards', 'red');

    expect($component->html())->not->toContain('wire:click="loadMoreCards(&#039;red&#039;)"');
});

it('ignores a load more request for an unknown column', function (): void {
    $component = Livewire::test(BoardComponent::class)->call('loadMoreCards', 'purple')->call('loadMoreCards', ['red']);

    expect($component->get('boardLimits'))->toBe([]);
});

it('refuses to let the browser set the limits', function (): void {
    Livewire::test(BoardComponent::class)->set('boardLimits', ['red' => 9999]);
})->throws(Exception::class, 'Cannot update locked property');

it('caps how many cards a column may ask for', function (): void {
    $component = Livewire::test(BoardComponent::class)->set('pageSize', 5000);

    expect($component->instance()->limitOf('red'))->toBe(1000);

    $component->call('loadMoreCards', 'red');

    expect($component->instance()->limitOf('red'))->toBe(1000);
});

it('leaves the host query untouched when it cuts a slice', function (): void {
    expect(Livewire::test(BoardComponent::class)->instance()->rowsAfterSlicing())->toBe(6);
});

it('offers every other column in the move to select and not the card\'s own', function (): void {
    $dom = Html::render('{!! $html !!}', ['html' => Livewire::test(BoardComponent::class)->html()]);

    $select = $dom->querySelectorAll('[data-board-column]')[0]->querySelector('[data-board-card] select');
    $values = array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($select->querySelectorAll('option')));

    expect($values)->toBe(['', 'green', 'yellow'])
        ->and($select->getAttribute('aria-label'))->toStartWith('Move ');
});

it('tells the host apart a move to the top from a move to the end of the column', function (): void {
    $component = Livewire::test(BoardComponent::class)
        ->call('moveCard', '1', 'green', 0, null)
        ->call('moveCard', '1', 'green', 100000, null)
        ->call('moveCard', '1', 'green', 99999, null);

    expect(array_column($component->get('moves'), 'toEnd'))->toBe([false, true, false]);
});

it('slices a query builder and an eloquent builder the same way', function (): void {
    $component = Livewire::test(BoardComponent::class);

    expect($component->instance()->sliceSizes())->toBe([2, 2])
        ->and($component->instance()->sliceHasMore())->toBe([true, true]);
});

it('lets a server error other than a refusal through', function (): void {
    Livewire::test(BoardComponent::class)->set('outcome', 'broken')->call('moveCard', '1', 'red', 0)->assertStatus(500);
});
