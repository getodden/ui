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

it('moves a card only when the Move button is pressed, never on the select alone', function (): void {
    $dom = Html::render(<<<'BLADE'
    <x-ui::board label="Deals" :columns="['a' => 'Alpha', 'b' => 'Beta']">
        <x-ui::board.column column="a" label="Alpha" :count="1">
            <x-ui::board.card id="9" label="Nine">Body</x-ui::board.card>
        </x-ui::board.column>
    </x-ui::board>
    BLADE);

    $board = $dom->querySelector('[role=group]');
    $confirm = $dom->querySelector('[data-board-card] button[data-board-confirm]');

    expect($board->hasAttribute('x-on:change'))->toBeFalse()
        ->and($confirm->getAttribute('aria-label'))->toBe('Move Nine to the chosen column')
        ->and(trim($confirm->textContent))->toBe('Move');
});

it('marks the controls aria-disabled while a move is pending instead of disabling the focused one', function (): void {
    $dom = Html::render(<<<'BLADE'
    <x-ui::board label="Deals" :columns="['a' => 'Alpha', 'b' => 'Beta']">
        <x-ui::board.column column="a" label="Alpha" :count="1">
            <x-ui::board.card id="9" label="Nine">Body</x-ui::board.card>
        </x-ui::board.column>
    </x-ui::board>
    BLADE);

    foreach ($dom->querySelectorAll('[data-board-card] select, [data-board-card] button') as $control) {
        expect($control->hasAttribute('x-bind:disabled'))->toBeFalse()
            ->and($control->getAttribute('x-bind:aria-disabled'))->toBe("pending ? 'true' : null");
    }
});

it('gives every card a wire:key unless the host sets one', function (): void {
    $dom = Html::render(<<<'BLADE'
    <x-ui::board label="Deals" :columns="['a' => 'Alpha']">
        <x-ui::board.column column="a" label="Alpha" :count="2">
            <x-ui::board.card id="9" label="Nine">Body</x-ui::board.card>
            <x-ui::board.card wire:key="deal-10" id="10" label="Ten">Body</x-ui::board.card>
        </x-ui::board.column>
    </x-ui::board>
    BLADE);

    $keys = array_map(fn ($card) => $card->getAttribute('wire:key'), iterator_to_array($dom->querySelectorAll('[data-board-card]')));

    expect($keys)->toBe(['ui-board-card-9', 'deal-10']);
});

it('leaves out the shift buttons on a board that is not reorderable', function (): void {
    $dom = Html::render(<<<'BLADE'
    <x-ui::board label="Deals" :columns="['a' => 'Alpha', 'b' => 'Beta']" :reorderable="false">
        <x-ui::board.column column="a" label="Alpha" :count="1">
            <x-ui::board.card id="9" label="Nine">Body</x-ui::board.card>
        </x-ui::board.column>
    </x-ui::board>
    BLADE);

    expect($dom->querySelector('[data-board-shift]'))->toBeNull()
        ->and($dom->querySelector('[data-board-card] select'))->not->toBeNull()
        ->and($dom->querySelector('[data-board-card] [data-board-confirm]'))->not->toBeNull();
});

it('renders cards without drag handle or controls when the board is not movable or the card is locked', function (bool $movable, bool $locked): void {
    $dom = Html::render(<<<'BLADE'
    <x-ui::board label="Deals" :columns="['a' => 'Alpha', 'b' => 'Beta']" :movable="$movable">
        <x-ui::board.column column="a" label="Alpha" :count="1">
            <x-ui::board.card id="9" label="Nine" :locked="$locked">Body</x-ui::board.card>
        </x-ui::board.column>
    </x-ui::board>
    BLADE, ['movable' => $movable, 'locked' => $locked]);

    $card = $dom->querySelector('[data-board-card]');

    expect($card->getAttribute('draggable'))->toBe('false')
        ->and($card->querySelector('select, button'))->toBeNull();
})->with([[false, false], [true, true], [false, true]]);

it('sets the heading level of a column and keeps it between 2 and 6', function (int $given, string $tag): void {
    $dom = Html::render('<x-ui::board label="Deals"><x-ui::board.column column="a" label="Alpha" :heading-level="$level"></x-ui::board.column></x-ui::board>', ['level' => $given]);

    expect($dom->querySelector('[data-board-heading]')->tagName)->toBe($tag);
})->with([[2, 'H2'], [5, 'H5'], [1, 'H2'], [9, 'H6']]);

it('marks a column that has more cards than shown', function (): void {
    $dom = Html::render('<x-ui::board label="Deals"><x-ui::board.column column="a" label="Alpha" :has-more="true"></x-ui::board.column><x-ui::board.column column="b" label="Beta"></x-ui::board.column></x-ui::board>');

    expect($dom->querySelector('[data-board-list="a"]')->hasAttribute('data-board-more'))->toBeTrue()
        ->and($dom->querySelector('[data-board-list="b"]')->hasAttribute('data-board-more'))->toBeFalse();
});

it('renders up and down buttons and a focusable heading for the keyboard', function (): void {
    $dom = Html::render(<<<'BLADE'
    <x-ui::board label="Deals" :columns="['a' => 'Alpha']">
        <x-ui::board.column column="a" label="Alpha" :count="1">
            <x-ui::board.card id="9" label="Nine">Body</x-ui::board.card>
        </x-ui::board.column>
    </x-ui::board>
    BLADE);

    $buttons = $dom->querySelectorAll('[data-board-card] button[data-board-shift]');

    expect($buttons)->toHaveCount(2)
        ->and($buttons[0]->getAttribute('data-board-shift'))->toBe('-1')
        ->and($buttons[0]->getAttribute('aria-label'))->toBe('Move Nine up')
        ->and($buttons[1]->getAttribute('data-board-shift'))->toBe('1')
        ->and($buttons[1]->getAttribute('aria-label'))->toBe('Move Nine down')
        ->and($dom->querySelector('h3[data-board-heading]')->getAttribute('tabindex'))->toBe('-1');
});
