<?php

declare(strict_types=1);

const EDIT_DIALOG = '[data-ui-modal=edit]';
const DETAILS_DIALOG = '[data-ui-modal=details]';

function dialogIsClosed(string $selector): string
{
    return 'getComputedStyle(document.querySelector(\''.$selector.'\')).display === "none"';
}

function focusIsInside(string $selector): string
{
    return 'document.querySelector(\''.$selector.'\').contains(document.activeElement)';
}

it('opens and closes the modal and returns focus to its opener', function (): void {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertScript(dialogIsClosed(EDIT_DIALOG))
        ->click('#open-edit')
        ->assertVisible(EDIT_DIALOG)
        ->assertSee('Edit account')
        ->assertScript(focusIsInside(EDIT_DIALOG))
        ->keys(':focus', 'Escape')
        ->assertScript(dialogIsClosed(EDIT_DIALOG))
        ->assertScript('document.activeElement.id === "open-edit"');
});

it('moves focus into the modal exactly once, onto the first focusable element', function (): void {
    $page = visit('/');

    $page->script('window.focusLog = []; document.addEventListener("focusin", (event) => window.focusLog.push(event.target.getAttribute("aria-label") ?? event.target.id))');

    $page->click('#open-edit')->assertVisible(EDIT_DIALOG)->assertScript(focusIsInside(EDIT_DIALOG));

    $log = $page->script('new Promise((resolve) => setTimeout(() => resolve(window.focusLog), 200))');

    expect($log)->toBe(['open-edit', 'Close']);
});

it('closes the modal from the backdrop and from the close button', function (): void {
    $page = visit('/');

    $page->click('#open-edit')->assertVisible(EDIT_DIALOG)
        ->click(EDIT_DIALOG.' [aria-label="Close"]')
        ->assertScript(dialogIsClosed(EDIT_DIALOG))
        ->click('#open-edit')->assertVisible(EDIT_DIALOG);

    $page->script('document.querySelector(\''.EDIT_DIALOG.' > [aria-hidden=true]\').click()');

    $page->assertScript(dialogIsClosed(EDIT_DIALOG));
});

it('closes only the top modal on Escape and keeps focus in the one beneath', function (): void {
    $page = visit('/');

    $page->click('#open-edit')->assertVisible(EDIT_DIALOG)
        ->click('#open-details')->assertVisible(DETAILS_DIALOG)
        ->assertScript(focusIsInside(DETAILS_DIALOG))
        ->keys(':focus', 'Escape')
        ->assertScript(dialogIsClosed(DETAILS_DIALOG))
        ->assertVisible(EDIT_DIALOG)
        ->assertScript(focusIsInside(EDIT_DIALOG))
        ->assertScript('document.activeElement.id === "open-details"')
        ->keys(':focus', 'Escape')
        ->assertScript(dialogIsClosed(EDIT_DIALOG));
});

it('shows a toast from the server and lets the visitor dismiss it', function (): void {
    visit('/')
        ->click('#warn')
        ->assertSee('Careful now')
        ->click('[aria-live] [aria-label="Dismiss"]')
        ->assertDontSee('Careful now');
});

it('holds a toast while the pointer is over it and lets it go afterwards', function (): void {
    $page = visit('/');

    $page->click('#warn')->assertSee('Careful now')->hover('[data-toast-id]');

    $stillThere = $page->script('new Promise((resolve) => setTimeout(() => resolve(document.querySelector("[data-toast-id]") !== null), 2200))');

    expect($stillThere)->toBeTrue();

    $page->hover('#open-edit')->assertDontSee('Careful now');
});

it('moves focus to the previous toast when the focused one is dismissed', function (): void {
    $page = visit('/');

    $page->click('#warn')->assertSee('Careful now')
        ->click('#warn')
        ->assertScript('document.querySelectorAll("[data-toast-id]").length === 2');

    $page->script('document.querySelectorAll("[data-toast-id] button")[1].focus()');
    $page->keys(':focus', 'Enter')
        ->assertScript('document.querySelectorAll("[data-toast-id]").length === 1')
        ->assertScript('document.activeElement.closest("[data-toast-id]") !== null');
});

it('shows a toast flashed before a redirect on the next page', function (): void {
    visit('/')
        ->click('#leave')
        ->assertSee('Saved for later')
        ->assertScript('document.querySelector("[aria-live]").textContent.includes("Saved for later")');
});

it('asks for confirmation, focuses cancel on a danger confirmation and runs the action', function (): void {
    $page = visit('/');

    $page->click('#delete')
        ->assertSee('Delete this item?')
        ->assertScript('document.activeElement.textContent.trim() === "Cancel"');

    $page->click('Delete item')
        ->assertSee('Deleted item 7')
        ->assertDontSee('Delete this item?');
});

it('drops the pending confirmation when it is dismissed', function (): void {
    $page = visit('/');

    $page->click('#delete')->assertSee('Delete this item?')
        ->assertScript('document.activeElement.textContent.trim() === "Cancel"')
        ->keys(':focus', 'Escape')
        ->assertDontSee('Delete this item?')
        ->assertScript('document.activeElement.id === "delete"')
        ->assertDontSee('Deleted item 7');
});

it('has no accessibility issues', function (): void {
    visit('/')->assertNoAccessibilityIssues();
});

it('has no accessibility issues while the modal is open', function (): void {
    visit('/')->click('#open-edit')->assertVisible(EDIT_DIALOG)->assertNoAccessibilityIssues();
});

it('returns focus to the opener when a server action removes an open modal', function (): void {
    $page = visit('/');

    $page->click('#open-server')
        ->assertVisible('[role=dialog]:has(#close-server)')
        ->assertScript('document.querySelector("[role=dialog]:has(#close-server)").contains(document.activeElement)');

    $page->click('#close-server')
        ->assertScript('document.querySelector("#close-server") === null')
        ->assertScript('document.activeElement.id === "open-server"');
});

it('puts a confirmation declared earlier in the DOM on top of the modal that opens it', function (): void {
    $page = visit('/');

    $page->click('#open-edit')->assertVisible(EDIT_DIALOG)
        ->click('#delete-in-modal')
        ->assertSee('Delete this item?');

    $page->assertScript('(() => { const dialog = document.querySelector("[role=dialog]:has(button[data-autofocus])"); const box = dialog.querySelector("h2").getBoundingClientRect(); const top = document.elementFromPoint(box.x + box.width / 2, box.y + box.height / 2); return dialog.contains(top) })()')
        ->assertScript('document.activeElement.textContent.trim() === "Cancel"');
});

it('marks only the clicked button busy while its action runs', function (): void {
    $page = visit('/');

    $page->script('window.busyLog = []; new MutationObserver(() => window.busyLog.push([...document.querySelectorAll("[aria-busy=true]")].map((element) => element.id))).observe(document.body, { attributes: true, attributeFilter: ["aria-busy"], subtree: true })');

    $page->click('#delete-a');

    $busy = $page->script('new Promise((resolve) => setTimeout(() => resolve(window.busyLog), 1200))');

    expect($busy)->not->toBeEmpty()
        ->and(collect($busy)->flatten()->unique()->values()->all())->toBe(['delete-a']);
});

it('moves focus to the next focusable element when a focused alert is dismissed', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("#second-alert button").focus()');

    $page->keys(':focus', 'Enter')
        ->assertScript('getComputedStyle(document.querySelector("#second-alert")).display === "none"')
        ->assertScript('document.activeElement.id === "after-alerts"');
});

it('moves focus to the parent region when a dismissed alert has nothing focusable after it', function (): void {
    $page = visit('/');

    $page->script('(() => { const alert = document.querySelector("#second-alert"); while (alert.nextElementSibling) { alert.nextElementSibling.remove() } alert.querySelector("button").focus() })()');

    $page->keys(':focus', 'Enter')
        ->assertScript('getComputedStyle(document.querySelector("#second-alert")).display === "none"')
        ->assertScript('document.activeElement === document.querySelector("#second-alert").parentElement');
});
