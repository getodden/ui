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

function lastMove(): string
{
    return 'JSON.stringify(Livewire.all().find((component) => component.name === "board-demo").$wire.lastMove)';
}

function boardStatus(): string
{
    return 'document.querySelector("#fruit-board [role=status]").textContent.trim()';
}

it('moves a card with the move to select, keeps it after the server renders and announces it', function (): void {
    $page = visit('/');

    $page->assertScript(cardsInColumn('red').' === \'["Apple","Cherry","Cranberry"]\'')
        ->select('#fruit-board [data-board-card="1"] select', 'yellow')
        ->click('#fruit-board [data-board-card="1"] [data-board-confirm]')
        ->wait(0.5)
        ->assertScript(cardsInColumn('red').' === \'["Cherry","Cranberry","Dragonfruit"]\'')
        ->assertScript(cardsInColumn('yellow').' === \'["Apple","Banana","Lemon"]\'')
        ->assertScript(boardStatus().' === "Apple moved to Yellow, position 1 of 3"');

    expect(Fruit::query()->find(1)->colour)->toBe('yellow');
});

it('puts the card back and says so when the host refuses the move', function (): void {
    $page = visit('/');

    $page->select('#fruit-board [data-board-card="1"] select', 'purple')
        ->click('#fruit-board [data-board-card="1"] [data-board-confirm]')
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

it('reaches the move to select with Tab and moves only after Enter on the Move button', function (): void {
    $page = visit('/');

    $page->keys('#fruit-board [data-board-column][data-board-label="Red"] [data-board-heading]', 'Tab')
        ->assertScript('document.activeElement.getAttribute("aria-label") === "Move Apple"')
        ->keys('#fruit-board [data-board-card="1"] select', 'y')
        ->wait(0.5)
        ->assertScript(cardsInColumn('red').' === \'["Apple","Cherry","Cranberry"]\'')
        ->keys('#fruit-board [data-board-card="1"] select', 'Tab')
        ->assertScript('document.activeElement.hasAttribute("data-board-confirm")')
        ->keys('#fruit-board [data-board-card="1"] [data-board-confirm]', 'Enter')
        ->wait(0.5)
        ->assertScript(cardsInColumn('yellow').' === \'["Apple","Banana","Lemon"]\'')
        ->assertScript('document.activeElement.getAttribute("aria-label") === "Move Apple"');

    expect(Fruit::query()->find(1)->colour)->toBe('yellow');
});

it('announces the same refusal again when it repeats', function (): void {
    $page = visit('/');

    $page->script('window.statusMutations = 0; new MutationObserver((records) => (window.statusMutations += records.length)).observe(document.querySelector("#fruit-board [role=status]"), { childList: true, characterData: true, subtree: true })');

    $page->select('#fruit-board [data-board-card="1"] select', 'purple')
        ->click('#fruit-board [data-board-card="1"] [data-board-confirm]')
        ->wait(0.5)
        ->assertScript(boardStatus().' === "Apple could not be moved and is back in Red"');

    $first = $page->script('window.statusMutations');

    $page->select('#fruit-board [data-board-card="1"] select', 'purple')
        ->click('#fruit-board [data-board-card="1"] [data-board-confirm]')
        ->wait(0.5)
        ->assertScript('window.statusMutations > '.$first);
});

it('moves focus to the column heading and announces without a position when the card is no longer shown', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("#fruit-board [data-board-label=\'Pineapple\'] select").focus()');

    $page->select('#fruit-board [data-board-label="Pineapple"] select', 'green')
        ->click('#fruit-board [data-board-label="Pineapple"] [data-board-confirm]')
        ->wait(0.5)
        ->assertScript('document.querySelector("#fruit-board [data-board-label=\'Pineapple\']") === null')
        ->assertScript('document.activeElement.hasAttribute("data-board-heading") && document.activeElement.textContent.trim() === "Green"')
        ->assertScript(boardStatus().' === "Pineapple moved to Green"');
});

it('moves a card within its column with the down button, sends its place and keeps focus on the button', function (): void {
    $page = visit('/?manual=1');

    $page->click('#fruit-board [data-board-card="1"] [data-board-shift="1"]')
        ->wait(0.5)
        ->assertScript(boardStatus().' === "Apple moved to Red, position 1 of 3"')
        ->assertScript('document.activeElement.dataset.boardShift === "1"')
        ->assertScript(lastMove().' === \'{"card":"1","column":"red","position":1,"after":"8"}\'');

    expect(Fruit::query()->find(1)->colour)->toBe('red');
});

it('sends no card to sit below when a card moves to the top', function (): void {
    $page = visit('/?manual=1');

    $page->click('#fruit-board [data-board-card="8"] [data-board-shift="-1"]')
        ->wait(0.5)
        ->assertScript(lastMove().' === \'{"card":"8","column":"red","position":0,"after":null}\'');
});

it('sends the card at the end of the shown cards as the one to sit below', function (): void {
    $page = visit('/');

    $page->select('#fruit-board [data-board-card="1"] select', 'yellow')
        ->click('#fruit-board [data-board-card="1"] [data-board-confirm]')
        ->wait(0.5)
        ->assertScript(lastMove().' === \'{"card":"1","column":"yellow","position":3,"after":"28"}\'');
});

it('sends the end position when the target column has more cards than are shown', function (): void {
    $page = visit('/');

    $page->select('#fruit-board [data-board-card="1"] select', 'green')
        ->click('#fruit-board [data-board-card="1"] [data-board-confirm]')
        ->wait(0.5)
        ->assertScript(lastMove().' === \'{"card":"1","column":"green","position":100000,"after":null}\'');
});

it('does nothing when a card is moved inside a column that is not reorderable', function (): void {
    $page = visit('/');

    $page->assertScript('document.querySelector("#fruit-board [data-board-shift]") === null')
        ->drag('#fruit-board [data-board-card="1"] > div:first-child', '#fruit-board [data-board-list="red"]')
        ->wait(0.5)
        ->assertScript(lastMove().' === "[]"')
        ->assertScript(cardsInColumn('red').' === \'["Apple","Cherry","Cranberry"]\'');
});

it('puts the card back, says the request failed and enables the controls again when the request fails', function (): void {
    $page = visit('/');

    $page->click('#fruit-board [data-board-list="red"] ~ div button')
        ->wait(0.5);

    $page->script('window.fetch = () => Promise.reject(new TypeError("offline")); true');

    $page->select('#fruit-board [data-board-label="Dragonfruit"] select', 'green')
        ->click('#fruit-board [data-board-label="Dragonfruit"] [data-board-confirm]')
        ->wait(1)
        ->assertScript(cardsInColumn('red').' === \'["Apple","Cherry","Cranberry","Dragonfruit","Lychee","Raspberry"]\'')
        ->assertScript(columnCount('red').' === "6"')
        ->assertScript(boardStatus().' === "Dragonfruit could not be moved because the request failed and is back in Red"')
        ->assertScript('document.querySelector("#fruit-board").getAttribute("aria-busy") === null')
        ->assertScript('document.querySelector("#fruit-board [data-board-label=\'Dragonfruit\'] select").getAttribute("aria-disabled") === null');

    expect(Fruit::query()->where('name', 'Dragonfruit')->value('colour'))->toBe('red');
});

it('shows no drag handle or controls for a viewer who may not move cards', function (): void {
    $page = visit('/?viewer=1');

    $page->assertScript('document.querySelectorAll("#fruit-board [data-board-card]").length > 0')
        ->assertScript('document.querySelectorAll("#fruit-board [data-board-card] select, #fruit-board [data-board-card] button").length === 0')
        ->assertScript('document.querySelector("#fruit-board [data-board-card]").getAttribute("draggable") === "false"');
});
