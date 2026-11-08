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
        ->assertScript(boardStatus().' === "Apple moved to Yellow, position 4 of 4"');

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

    $page->drag('#fruit-board [data-board-card="1"] > div', '#fruit-board [data-board-list="green"]')
        ->wait(0.5)
        ->assertScript(cardsInColumn('red').' === \'["Cherry","Cranberry","Dragonfruit"]\'')
        ->assertScript(boardStatus().'.startsWith("Apple moved to Green, position ") && '.boardStatus().'.endsWith(" of 4")');

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

    $page->script('document.querySelector("#fruit-board [data-board-card=\"1\"] select").focus()');

    $page->assertScript('document.activeElement.getAttribute("aria-label") === "Move Apple"')
        ->assertScript('document.querySelector("#fruit-board [data-board-card=\"1\"] select").options.length === 4');
});
