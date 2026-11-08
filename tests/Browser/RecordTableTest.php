<?php

declare(strict_types=1);

const FRUIT_TABLE = '#fruit-table';
const FRUIT_NAMES = 'document.querySelectorAll("#fruit-table tbody th").length';

function firstFruit(): string
{
    return 'document.querySelector("#fruit-table tbody tr:first-child th").textContent.trim()';
}

function sortAttribute(string $column): string
{
    return 'document.querySelector("#fruit-table thead th:nth-child('.$column.')").getAttribute("aria-sort")';
}

it('sorts when a header is clicked and flips aria-sort', function (): void {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertScript(sortAttribute('2').' === "ascending"')
        ->assertScript(sortAttribute('4').' === null')
        ->click('#fruit-table thead th:nth-child(2) button')
        ->assertScript(sortAttribute('2').' === "descending"')
        ->assertScript(firstFruit().' === "Raspberry"')
        ->click('#fruit-table thead th:nth-child(4) button')
        ->assertScript(sortAttribute('4').' === "ascending"')
        ->assertScript(sortAttribute('2').' === null')
        ->assertScript(firstFruit().' === "Lime"');
});

it('sorts from the keyboard', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("#fruit-table thead th:nth-child(4) button").focus()');

    $page->keys(':focus', 'Enter')
        ->assertScript(sortAttribute('4').' === "ascending"')
        ->assertScript(firstFruit().' === "Lime"')
        ->assertScript('document.activeElement.closest("th").getAttribute("aria-sort") === "ascending"');
});

it('reaches the header buttons with Tab in reading order', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("#fruit-table thead input[type=checkbox]").focus()');
    $page->keys(':focus', 'Tab')
        ->assertScript('document.activeElement.textContent.trim() === "Name"')
        ->keys(':focus', 'Tab')
        ->assertScript('document.activeElement.textContent.trim() === "Price"');
});

it('filters rows as the visitor types and keeps the search in the url', function (): void {
    $page = visit('/');

    $page->type('#fruit-search', 'berry')
        ->assertSeeIn(FRUIT_TABLE, 'Raspberry')
        ->assertDontSeeIn(FRUIT_TABLE, 'Banana')
        ->assertScript(FRUIT_NAMES.' === 4')
        ->assertScript('new URL(location.href).searchParams.get("search") === "berry"');
});

it('filters by colour and updates the url', function (): void {
    $page = visit('/');

    $page->select('#fruit-colour', 'purple')
        ->assertSeeIn(FRUIT_TABLE, 'Plum')
        ->assertDontSeeIn(FRUIT_TABLE, 'Banana')
        ->assertScript(FRUIT_NAMES.' === 4')
        ->assertScript('new URL(location.href).searchParams.get("filters[colour]") === "purple"');
});

it('moves between pages and back to the first page when the filter changes', function (): void {
    $page = visit('/');

    $page->assertSeeIn(FRUIT_TABLE, 'Apple')->assertDontSeeIn(FRUIT_TABLE, 'Kiwi')
        ->click('[aria-label="Go to page 2"]')
        ->assertSeeIn(FRUIT_TABLE, 'Kiwi')
        ->assertDontSeeIn(FRUIT_TABLE, 'Apple')
        ->assertScript('document.querySelector("nav[aria-label=Pagination] [aria-current=page]").textContent.includes("2")')
        ->click('Next')
        ->assertScript('document.querySelector("nav[aria-label=Pagination] [aria-current=page]").textContent.includes("3")')
        ->select('#fruit-colour', 'green')
        ->assertSeeIn(FRUIT_TABLE, 'Avocado')
        ->assertDontSee('Pagination');
});

it('changes the number of rows per page', function (): void {
    $page = visit('/');

    $page->assertScript(FRUIT_NAMES.' === 10')
        ->select('#fruit-per-page', '25')
        ->assertScript(FRUIT_NAMES.' === 25');
});

it('shows the empty state when nothing matches', function (): void {
    $page = visit('/');

    $page->type('#fruit-search', 'zzzz')
        ->assertSee('No fruit matches your search.')
        ->assertScript('document.querySelector("#fruit-table tbody td").getAttribute("colspan") === "5"')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

it('shows the current state from the url on load', function (): void {
    $page = visit('/?search=berry&sort=price&direction=desc&filters[colour]=red');

    $page->assertSee('Raspberry')
        ->assertScript(sortAttribute('4').' === "descending"')
        ->assertScript(firstFruit().' === "Raspberry"')
        ->assertScript(FRUIT_NAMES.' === 2');
});

it('survives hostile query strings in a real browser', function (): void {
    visit('/?sort=password&direction=sideways&filters[colour][]=x&perPage=100000&search[]=x')
        ->assertNoJavaScriptErrors()
        ->assertSee('Apple')
        ->assertScript(FRUIT_NAMES.' === 10');
});

it('marks the table busy and announces loading while a request runs', function (): void {
    $page = visit('/');

    $page->script('window.busyLog = []; const table = document.querySelector("#fruit-table"); const message = table.closest("section").querySelector("[role=status] span"); new MutationObserver(() => window.busyLog.push([table.getAttribute("aria-busy"), getComputedStyle(message).display !== "none"])).observe(table, { attributes: true, attributeFilter: ["aria-busy"] })');

    $page->assertScript('document.querySelector("#fruit-table").getAttribute("aria-busy") === null')
        ->select('#fruit-per-page', '25')
        ->assertScript('window.busyLog.length === 2');

    expect($page->script('window.busyLog'))->toBe([['true', true], [null, false]]);
});

it('has a polite status region from the start that reports the range', function (): void {
    visit('/')->assertScript('document.querySelector("#fruit-table").closest("section").querySelector("[role=status]").textContent.includes("Showing 1 to 10 of 30 results")');
});

it('keeps focus inside the pagination after activating a page and after reaching the last page', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("[aria-label=\\"Go to page 2\\"]").focus()');
    $page->keys(':focus', 'Enter')
        ->assertScript('document.querySelector("nav[aria-label=Pagination] [aria-current=page]").textContent.includes("2")')
        ->assertScript('document.querySelector("nav[aria-label=Pagination]").contains(document.activeElement)')
        ->assertScript('document.activeElement.getAttribute("aria-current") === "page"');

    $page->script('[...document.querySelectorAll("nav[aria-label=Pagination] button")].find((b) => b.textContent.trim() === "Next").focus()');
    $page->keys(':focus', 'Enter')
        ->assertScript('document.querySelector("nav[aria-label=Pagination] [aria-current=page]").textContent.includes("3")')
        ->assertScript('document.querySelector("nav[aria-label=Pagination]").contains(document.activeElement)')
        ->assertScript('document.activeElement.textContent.trim() === "Next" && document.activeElement.getAttribute("aria-disabled") === "true"');

    $page->keys(':focus', 'Enter')
        ->assertScript('document.querySelector("nav[aria-label=Pagination]").contains(document.activeElement)');
});

it('does not mark the table busy for an unrelated action in the component', function (): void {
    $page = visit('/');

    $page->script('window.busyLog = []; const table = document.querySelector("#fruit-table"); new MutationObserver(() => window.busyLog.push(table.getAttribute("aria-busy"))).observe(table, { attributes: true, attributeFilter: ["aria-busy"] })');

    $page->click('#fruit-unrelated')
        ->assertScript('document.querySelector("#fruit-unrelated-count").textContent === "1"');

    expect($page->script('window.busyLog'))->toBe([]);
});

it('loads a page with invalid UTF-8 in the query string', function (): void {
    visit('/?search=%ff&sort=%ff&filters[colour]=%ff&perPage=%ff')
        ->assertSee('Odden UI demo')
        ->assertSee('No fruit matches your search.');
});

it('has no accessibility issues', function (): void {
    visit('/')->assertNoAccessibilityIssues();
});

it('selects the page, announces the count and runs a bulk action', function (): void {
    $page = visit('/');

    $page->click('#fruit-table thead input[type=checkbox]')
        ->assertScript('document.querySelectorAll("#fruit-table tbody input[type=checkbox]:checked").length === 10')
        ->assertSee('Describe selected')
        ->assertScript('document.querySelector("#fruit-table").closest("div.space-y-4").querySelector("[role=status]").textContent.includes("10 rows selected")')
        ->click('#fruit-table thead input[type=checkbox]')
        ->assertScript('document.querySelectorAll("#fruit-table tbody input[type=checkbox]:checked").length === 0')
        ->click('#fruit-table tbody tr:nth-child(2) input[type=checkbox]')
        ->click('Describe selected')
        ->assertScript('document.querySelector("#fruit-last-action").textContent === "Selected: Apricot"')
        ->assertScript('document.querySelectorAll("#fruit-table tbody input[type=checkbox]:checked").length === 0');
});

it('clears the selection when the tab changes', function (): void {
    $page = visit('/');

    $page->click('#fruit-table tbody tr:first-child input[type=checkbox]')
        ->click('Purple')
        ->assertScript('document.querySelectorAll("#fruit-table tbody input[type=checkbox]:checked").length === 0')
        ->assertScript('new URL(location.href).searchParams.get("tab") === "purple"')
        ->assertScript('document.querySelector("nav button[aria-current]").textContent.trim() === "Purple"');
});

it('opens the row menu from the keyboard, moves with arrows and closes with Escape', function (): void {
    $page = visit('/');

    $trigger = '#fruit-table tbody tr:first-child button[aria-haspopup=menu]';
    $page->script('document.querySelector("'.$trigger.'").focus()');

    $page->keys(':focus', 'Enter')
        ->assertScript('document.querySelector("'.$trigger.'").getAttribute("aria-expanded") === "true"')
        ->assertScript('document.activeElement.getAttribute("role") === "menuitem"')
        ->keys(':focus', 'Escape')
        ->assertScript('document.querySelector("'.$trigger.'").getAttribute("aria-expanded") === "false"')
        ->assertScript('document.activeElement === document.querySelector("'.$trigger.'")')
        ->keys(':focus', 'ArrowDown')
        ->wait(0.3)
        ->assertScript('document.activeElement.getAttribute("role") === "menuitem"')
        ->keys(':focus', 'Enter')
        ->assertSee('Inspected: Apple');
});
