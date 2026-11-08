<?php

declare(strict_types=1);

use Workbench\App\Models\Fruit;

function cardsInColumn(string $column): string
{
    return 'JSON.stringify([...document.querySelectorAll("#fruit-board [data-board-list=\''.$column.'\'] > [data-board-card]")].map((card) => card.dataset.boardLabel))';
}

function columnCount(string $column): string
{
    return 'document.querySelector("#fruit-board [data-board-list=\''.$column.'\']").closest("section").querySelector("[data-board-count]").textContent.trim()';
}

function boardStatus(): string
{
    return 'document.querySelector("#fruit-board [role=status]").textContent.trim()';
}

it('moves a card with the move to select, keeps it after the server renders and announces it', function (): void {
    $page = visit('/');

    $page->assertScript(cardsInColumn('red').' === \'["Apple","Cherry","Cranberry"]\'')
        ->select('#fruit-board [data-board-card="1"] select', 'yellow')
        ->wait(0.5)
        ->assertScript(cardsInColumn('red').' === \'["Cherry","Cranberry","Dragonfruit"]\'')
        ->assertScript(cardsInColumn('yellow').' === \'["Apple","Banana","Lemon"]\'')
        ->assertScript(boardStatus().' === "Apple moved to Yellow, position 1 of 3"');

    expect(Fruit::query()->find(1)->colour)->toBe('yellow');
});

it('puts the card back and says so when the host refuses the move', function (): void {
    $page = visit('/');

    $page->select('#fruit-board [data-board-card="1"] select', 'purple')
        ->wait(0.5)
        ->assertScript(cardsInColumn('red').' === \'["Apple","Cherry","Cranberry"]\'')
        ->assertScript(columnCount('red').' === "6"')
        ->assertScript(columnCount('purple').' === "4"')
        ->assertScript(boardStatus().' === "Apple could not be moved and is back in Red"');

    expect(Fruit::query()->find(1)->colour)->toBe('red');
});

it('moves a card by dragging it to another column', function (): void {
    $page = visit('/');

    $page->drag('#fruit-board [data-board-card="1"] > div:first-child', '#fruit-board [data-board-list="green"]')
        ->wait(0.5)
        ->assertScript(cardsInColumn('red').' === \'["Cherry","Cranberry","Dragonfruit"]\'')
        ->assertScript(boardStatus().' === "Apple moved to Green, position 1 of 3"');

    expect(Fruit::query()->find(1)->colour)->toBe('green');
});

it('shows more cards in a column on request', function (): void {
    $page = visit('/');

    $page->assertScript('document.querySelectorAll("#fruit-board [data-board-list=\'red\'] > [data-board-card]").length === 3')
        ->click('#fruit-board [data-board-list="red"] ~ div button')
        ->wait(0.5)
        ->assertScript('document.querySelectorAll("#fruit-board [data-board-list=\'red\'] > [data-board-card]").length === 6');
});

it('reaches the move to select with Tab and changes it from the keyboard', function (): void {
    $page = visit('/');

    $page->keys('#fruit-board [data-board-column][data-board-label="Red"] [data-board-heading]', 'Tab')
        ->assertScript('document.activeElement.getAttribute("aria-label") === "Move Apple"')
        ->keys('#fruit-board [data-board-card="1"] select', 'y')
        ->wait(0.5)
        ->assertScript(cardsInColumn('yellow').' === \'["Apple","Banana","Lemon"]\'')
        ->assertScript('document.activeElement.getAttribute("aria-label") === "Move Apple"');

    expect(Fruit::query()->find(1)->colour)->toBe('yellow');
});

it('announces the same refusal again when it repeats', function (): void {
    $page = visit('/');

    $page->script('window.statusMutations = 0; new MutationObserver((records) => (window.statusMutations += records.length)).observe(document.querySelector("#fruit-board [role=status]"), { childList: true, characterData: true, subtree: true })');

    $page->select('#fruit-board [data-board-card="1"] select', 'purple')
        ->wait(0.5)
        ->assertScript(boardStatus().' === "Apple could not be moved and is back in Red"');

    $first = $page->script('window.statusMutations');

    $page->select('#fruit-board [data-board-card="1"] select', 'purple')
        ->wait(0.5)
        ->assertScript('window.statusMutations > '.$first);
});

it('moves focus to the column heading and announces without a position when the card is no longer shown', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("#fruit-board [data-board-label=\'Pineapple\'] select").focus()');

    $page->select('#fruit-board [data-board-label="Pineapple"] select', 'green')
        ->wait(0.5)
        ->assertScript('document.querySelector("#fruit-board [data-board-label=\'Pineapple\']") === null')
        ->assertScript('document.activeElement.hasAttribute("data-board-heading") && document.activeElement.textContent.trim() === "Green"')
        ->assertScript(boardStatus().' === "Pineapple moved to Green"');
});

it('moves a card within its column with the down and up buttons and keeps focus on the button', function (): void {
    $page = visit('/');

    $page->click('#fruit-board [data-board-card="1"] [data-board-shift="1"]')
        ->wait(0.5)
        ->assertScript(boardStatus().' === "Apple moved to Red, position 1 of 3"')
        ->assertScript('document.activeElement.dataset.boardShift === "1"');

    expect(Fruit::query()->find(1)->colour)->toBe('red');
});
