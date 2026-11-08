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

it('keeps keyboard focus on the page checkbox after a bulk action removes the bar', function (): void {
    $page = visit('/');

    $page->click('#fruit-table tbody tr:nth-child(2) input[type=checkbox]')
        ->click('Describe selected')
        ->wait(0.5)
        ->assertScript('document.activeElement === document.querySelector("#fruit-table thead input[type=checkbox]")');
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
        ->wait(0.5)
        ->assertScript('document.querySelector("'.$trigger.'").getAttribute("aria-expanded") === "true"')
        ->assertScript('document.activeElement.getAttribute("role") === "menuitem"')
        ->keys(':focus', 'Escape')
        ->wait(0.3)
        ->assertScript('document.querySelector("'.$trigger.'").getAttribute("aria-expanded") === "false"')
        ->assertScript('document.activeElement === document.querySelector("'.$trigger.'")')
        ->keys(':focus', 'ArrowDown')
        ->wait(0.3)
        ->assertScript('document.activeElement.getAttribute("role") === "menuitem"')
        ->keys(':focus', 'Enter')
        ->assertSee('Inspected: Apple');
});

function firstTrigger(): string
{
    return 'document.querySelector("#fruit-table tbody tr:first-child button[aria-haspopup=menu]")';
}

it('opens on the last item with ArrowUp, jumps with Home and End, and closes on Tab', function (): void {
    $page = visit('/');

    $page->script(firstTrigger().'.focus()');

    $page->keys(':focus', 'ArrowUp')
        ->wait(0.5)
        ->assertScript('document.activeElement === document.querySelector("#fruit-table tbody tr:first-child [role=menu] [role=menuitem]:last-child")')
        ->keys(':focus', 'Home')
        ->wait(0.3)
        ->assertScript('document.activeElement === document.querySelector("#fruit-table tbody tr:first-child [role=menu] [role=menuitem]")')
        ->keys(':focus', 'End')
        ->wait(0.3)
        ->assertScript('document.activeElement === document.querySelector("#fruit-table tbody tr:first-child [role=menu] [role=menuitem]:last-child")')
        ->keys(':focus', 'Tab')
        ->wait(0.3)
        ->assertScript(firstTrigger().'.getAttribute("aria-expanded") === "false"');
});

it('closes the row menu on a click outside', function (): void {
    $page = visit('/');

    $page->click('#fruit-table tbody tr:first-child button[aria-haspopup=menu]')
        ->assertScript(firstTrigger().'.getAttribute("aria-expanded") === "true"')
        ->script('document.querySelector("#fruit-table tbody tr:nth-child(3) th").click()');

    $page
        ->assertScript(firstTrigger().'.getAttribute("aria-expanded") === "false"');
});

it('closes the row menu on scroll and moves focus back only when it was inside the menu', function (): void {
    $page = visit('/');

    $page->script(firstTrigger().'.focus()');
    $page->keys(':focus', 'Enter')
        ->wait(0.5)
        ->assertScript('document.activeElement.getAttribute("role") === "menuitem"')
        ->script('window.dispatchEvent(new Event("scroll"))');

    $page->assertScript(firstTrigger().'.getAttribute("aria-expanded") === "false"')
        ->assertScript('document.activeElement === '.firstTrigger());

    $page->script(firstTrigger().'.click()');
    $page->wait(0.5);
    $page->script('document.activeElement.blur(); window.dispatchEvent(new Event("scroll"))');

    $page->assertScript(firstTrigger().'.getAttribute("aria-expanded") === "false"')
        ->assertScript('document.activeElement !== '.firstTrigger());
});

it('opens the row menu above the trigger when there is no room below', function (): void {
    $page = visit('/');

    $page->script('document.body.style.paddingBottom = "2000px"; const row = document.querySelector("#fruit-table tbody tr:last-child"); window.scrollTo(0, row.getBoundingClientRect().bottom + window.scrollY - window.innerHeight + 4)');
    $page->wait(0.5);
    $page->script('document.querySelector("#fruit-table tbody tr:last-child button[aria-haspopup=menu]").click()');

    $page->wait(0.5)
        ->assertScript('(() => { const trigger = document.querySelector("#fruit-table tbody tr:last-child button[aria-haspopup=menu]").getBoundingClientRect(); const menu = document.querySelector("#fruit-table tbody tr:last-child [role=menu]").getBoundingClientRect(); return menu.bottom <= trigger.top + 1 })()');
});

it('keeps the page checkbox in step with the page rows and focuses it after clearing the selection', function (): void {
    $page = visit('/');

    $page->click('#fruit-table tbody tr:first-child input[type=checkbox]')
        ->wait(0.5)
        ->assertScript('document.querySelector("#fruit-table thead input[type=checkbox]").indeterminate === true');

    $page->script('const box = document.querySelector("#fruit-table thead input[type=checkbox]"); box.setAttribute("data-keys", JSON.stringify(JSON.parse(box.dataset.keys).slice(1)))');

    $page->wait(0.5)
        ->assertScript('document.querySelector("#fruit-table thead input[type=checkbox]").indeterminate === false')
        ->click('Clear selection')
        ->wait(0.5)
        ->assertScript('document.activeElement === document.querySelector("#fruit-table thead input[type=checkbox]")');
});

it('returns focus to the page checkbox after a confirmed bulk action removes the bar', function (): void {
    $page = visit('/');

    $page->click('#fruit-table tbody tr:nth-child(2) input[type=checkbox]')
        ->click('Remove selected')
        ->wait(0.5)
        ->click('Remove fruit')
        ->wait(0.8)
        ->assertScript('document.querySelector("#fruit-last-action").textContent === "Removed: 1"')
        ->assertScript('document.activeElement === document.querySelector("#fruit-table thead input[type=checkbox]")');
});

it('returns focus to a sortable header when a confirmed bulk action removes every row on the page', function (): void {
    $page = visit('/?search=Kiwi');

    $page->click('#fruit-table thead input[type=checkbox]')
        ->click('Remove selected')
        ->wait(0.5)
        ->click('Remove fruit')
        ->wait(0.8)
        ->assertScript('document.querySelector("#fruit-table thead input[type=checkbox]").disabled')
        ->assertScript('document.activeElement === document.querySelector("#fruit-table thead button")');
});
