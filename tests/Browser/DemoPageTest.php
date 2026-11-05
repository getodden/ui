<?php

declare(strict_types=1);

const EDIT_DIALOG = '[aria-labelledby="ui-modal-edit-heading"]';
const EDIT_DIALOG_CLOSED = 'getComputedStyle(document.querySelector(\'[aria-labelledby="ui-modal-edit-heading"]\')).display === "none"';

it('opens and closes the modal and returns focus to its opener', function (): void {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertScript(EDIT_DIALOG_CLOSED)
        ->click('#open-edit')
        ->assertVisible(EDIT_DIALOG)
        ->assertSee('Edit account')
        ->keys(':focus', 'Escape')
        ->assertScript(EDIT_DIALOG_CLOSED);

    usleep(200_000);

    expect($page->script('document.activeElement.id'))->toBe('open-edit');
});

it('keeps focus inside the open modal', function (): void {
    $page = visit('/');

    $page->click('#open-edit')->assertVisible(EDIT_DIALOG);

    usleep(300_000);

    expect($page->script('document.querySelector(\''.EDIT_DIALOG.'\').contains(document.activeElement)'))->toBeTrue();
});

it('closes the modal from the backdrop and from the close button', function (): void {
    $page = visit('/');

    $page->click('#open-edit')->assertVisible(EDIT_DIALOG)
        ->click(EDIT_DIALOG.' [aria-label="Close"]')
        ->assertScript(EDIT_DIALOG_CLOSED)
        ->click('#open-edit')->assertVisible(EDIT_DIALOG);

    $page->script('document.querySelector(\''.EDIT_DIALOG.' > [aria-hidden=true]\').click()');

    $page->assertScript(EDIT_DIALOG_CLOSED);
});

it('shows a toast from the server and lets the visitor dismiss it', function (): void {
    visit('/')
        ->click('#warn')
        ->assertSee('Careful now')
        ->click('[aria-live] [aria-label="Dismiss"]')
        ->assertDontSee('Careful now');
});

it('asks for confirmation, focuses cancel on a danger confirmation and runs the action', function (): void {
    $page = visit('/');

    $page->click('#delete')->assertSee('Delete this item?');

    usleep(300_000);

    expect($page->script('document.activeElement.textContent.trim()'))->toBe('Cancel');

    $page->click('Delete item')
        ->assertSee('Deleted item 7')
        ->assertDontSee('Delete this item?');
});

it('drops the pending confirmation when it is dismissed', function (): void {
    $page = visit('/');

    $page->click('#delete')->assertSee('Delete this item?')->keys(':focus', 'Escape');

    usleep(500_000);

    expect($page->script('document.activeElement.id'))->toBe('delete');

    $page->assertDontSee('Deleted item 7');
});

it('has no accessibility issues', function (): void {
    visit('/')->assertNoAccessibilityIssues();
});

it('has no accessibility issues while the modal is open', function (): void {
    visit('/')->click('#open-edit')->assertVisible(EDIT_DIALOG)->assertNoAccessibilityIssues();
});
