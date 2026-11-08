<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Odden\Ui\Tests\Support\Html;

it('throws without a label, aria-label or aria-labelledby', function (): void {
    Html::render('<x-ui::board><x-ui::board.column column="a" label="A"></x-ui::board.column></x-ui::board>');
})->throws(ViewException::class, 'A ui::board needs a label');

it('accepts an aria-label instead of a label', function (): void {
    $dom = Html::render('<x-ui::board aria-label="Deals"></x-ui::board>');

    expect($dom->querySelector('[role=group]')->getAttribute('aria-label'))->toBe('Deals');
});

it('renders a labelled column with its count and optional total', function (): void {
    $dom = Html::render('<x-ui::board label="Deals" :columns="[\'a\' => \'A\']"><x-ui::board.column column="a" label="Alpha" :count="4" total="$40"></x-ui::board.column></x-ui::board>');

    $section = $dom->querySelector('section[data-board-column]');

    expect($section->getAttribute('aria-labelledby'))->toBe($section->querySelector('h3')->getAttribute('id'))
        ->and(trim($section->querySelector('h3')->textContent))->toBe('Alpha')
        ->and(trim($section->querySelector('[data-board-count]')->textContent))->toBe('4')
        ->and(trim($section->querySelector('[data-board-total]')->textContent))->toBe('$40')
        ->and($section->querySelector('ul')->getAttribute('data-board-list'))->toBe('a');
});

it('leaves out the total and the show more button when they are not given', function (): void {
    $dom = Html::render('<x-ui::board label="Deals"><x-ui::board.column column="a" label="Alpha" :count="0"></x-ui::board.column></x-ui::board>');

    expect($dom->querySelector('[data-board-total]'))->toBeNull()
        ->and($dom->querySelector('section button'))->toBeNull();
});

it('renders a card with its id, a draggable handle and a move to select without the card\'s own column', function (): void {
    $dom = Html::render(<<<'BLADE'
    <x-ui::board label="Deals" :columns="['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma']">
        <x-ui::board.column column="b" label="Beta" :count="1">
            <x-ui::board.card id="9" label="Nine">Body</x-ui::board.card>
        </x-ui::board.column>
    </x-ui::board>
    BLADE);

    $card = $dom->querySelector('[data-board-card]');
    $values = array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($card->querySelectorAll('option')));

    expect($card->getAttribute('data-board-card'))->toBe('9')
        ->and($card->getAttribute('draggable'))->toBe('true')
        ->and($values)->toBe(['', 'a', 'c'])
        ->and($card->querySelector('select')->getAttribute('aria-label'))->toBe('Move Nine');
});

it('escapes ids and labels', function (): void {
    $dom = Html::render(<<<'BLADE'
    <x-ui::board label="Deals" :columns="['a' => 'Alpha']">
        <x-ui::board.column column="a" label="Al&quot;pha" :count="1">
            <x-ui::board.card :id="$id" :label="$id">Body</x-ui::board.card>
        </x-ui::board.column>
    </x-ui::board>
    BLADE, ['id' => '"><script>alert(1)</script>']);

    expect($dom->querySelector('script'))->toBeNull()
        ->and($dom->querySelector('[data-board-card]')->getAttribute('data-board-card'))->toBe('"><script>alert(1)</script>');
});

it('has a live region for announcing moves', function (): void {
    $dom = Html::render('<x-ui::board label="Deals"></x-ui::board>');

    $region = $dom->querySelector('[role=status]');

    expect($region->getAttribute('aria-live'))->toBe('polite')
        ->and($region->classList->contains('sr-only'))->toBeTrue();
});
