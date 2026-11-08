<?php

declare(strict_types=1);

const FORMS_WIRE = 'Livewire.find(document.querySelector("#forms-form").closest("[wire\\\\:id]").getAttribute("wire:id"))';
const CUSTOMER = '#customer';
const CUSTOMER_LISTBOX = '#customer-listbox';
const TAGS_BUTTON = '#ui-tags';
const TAGS_LISTBOX = '#ui-tags-listbox';
const MENU_TRIGGER = '#forms-menu-trigger';
const MENU = '#forms-menu-menu';

function formsSettled(string $script, int $milliseconds = 700): string
{
    return 'new Promise((resolve) => setTimeout(() => resolve('.$script.'), '.$milliseconds.'))';
}

function visibleOptions(string $listbox): string
{
    return '[...document.querySelectorAll("'.$listbox.' [role=option]")].map((option) => option.textContent.trim())';
}

function isHidden(string $selector): string
{
    return 'getComputedStyle(document.querySelector("'.$selector.'")).display === "none"';
}

function focusedId(): string
{
    return 'document.activeElement.id';
}

it('loads the forms without JavaScript errors or accessibility issues', function (): void {
    visit('/')->assertNoJavaScriptErrors()->assertNoAccessibilityIssues();
});

it('searches on the server after a pause and lists the matches', function (): void {
    $page = visit('/');

    $page->script('window.requests = 0; const original = window.fetch; window.fetch = (...arguments_) => { if (String(arguments_[0]).includes("livewire")) { window.requests++ } return original(...arguments_) }');

    $page->type(CUSTOMER, 'gra');

    expect($page->script(formsSettled(visibleOptions(CUSTOMER_LISTBOX))))->toBe(['Grace Hopper', 'Grace Kelly'])
        ->and($page->script('window.requests'))->toBe(1);

    $page->assertScript('document.querySelector("'.CUSTOMER.'").getAttribute("aria-expanded") === "true"')
        ->assertScript('[...document.querySelectorAll("[role=status]")].some((region) => region.textContent.includes("2 results available"))');
});

it('moves through the matches with the arrow keys and picks one with Enter', function (): void {
    $page = visit('/');

    $page->type(CUSTOMER, 'grace');
    $page->script(formsSettled('true'));

    $page->keys(CUSTOMER, 'ArrowDown')
        ->assertScript('document.querySelector("'.CUSTOMER.'").getAttribute("aria-activedescendant") === "customer-option-0"')
        ->keys(CUSTOMER, 'ArrowDown')
        ->assertScript('document.querySelector("'.CUSTOMER.'").getAttribute("aria-activedescendant") === "customer-option-1"')
        ->assertScript('document.getElementById("customer-option-1").textContent.trim() === "Grace Kelly"')
        ->keys(CUSTOMER, 'ArrowDown')
        ->assertScript('document.querySelector("'.CUSTOMER.'").getAttribute("aria-activedescendant") === "customer-option-0"')
        ->keys(CUSTOMER, 'ArrowUp')
        ->keys(CUSTOMER, 'Enter')
        ->assertScript('document.querySelector("'.CUSTOMER.'").value === "Grace Kelly"')
        ->assertScript(isHidden(CUSTOMER_LISTBOX))
        ->assertScript(FORMS_WIRE.'.customer === "2"')
        ->assertScript('document.querySelector("'.CUSTOMER.'").getAttribute("aria-expanded") === "false"');
});

it('picks a match with the pointer and keeps focus in the input', function (): void {
    $page = visit('/');

    $page->type(CUSTOMER, 'turing');
    $page->script(formsSettled('true'));

    $page->click('#customer-option-0')
        ->assertScript('document.querySelector("'.CUSTOMER.'").value === "Alan Turing"')
        ->assertScript(FORMS_WIRE.'.customer === "3"')
        ->assertScript('document.activeElement.id === "customer"');
});

it('forgets the chosen value as soon as the text is edited', function (): void {
    $page = visit('/');

    $page->type(CUSTOMER, 'turing');
    $page->script(formsSettled('true'));
    $page->click('#customer-option-0')->assertScript(FORMS_WIRE.'.customer === "3"');

    $page->type(CUSTOMER, 'Alan T')->assertScript(FORMS_WIRE.'.customer === ""');
});

it('closes the list on Escape without clearing the text, and clears unmatched text on blur', function (): void {
    $page = visit('/');

    $page->type(CUSTOMER, 'ada');
    $page->script(formsSettled('true'));

    $page->assertScript(visibleOptions(CUSTOMER_LISTBOX).'.length === 1')
        ->keys(CUSTOMER, 'Escape')
        ->assertScript(isHidden(CUSTOMER_LISTBOX))
        ->assertScript('document.querySelector("'.CUSTOMER.'").value === "ada"')
        ->keys(CUSTOMER, 'ArrowDown')
        ->assertScript(visibleOptions(CUSTOMER_LISTBOX).'.length === 1')
        ->click('#forms-header h1')
        ->assertScript('document.querySelector("'.CUSTOMER.'").value === ""');
});

it('says when nothing matches', function (): void {
    $page = visit('/');

    $page->type(CUSTOMER, 'zzzz');

    $page->script(formsSettled('true'));

    $page->assertSee('No matches')
        ->assertScript(isHidden(CUSTOMER_LISTBOX));
});

it('shows only the results of the latest search when an earlier one is slow', function (): void {
    $page = visit('/');

    $page->type(CUSTOMER, 'slow');
    $page->script('new Promise((resolve) => setTimeout(resolve, 350))');
    $page->type(CUSTOMER, 'ada');

    expect($page->script(formsSettled(visibleOptions(CUSTOMER_LISTBOX), 2200)))->toBe(['Ada Lovelace']);
});

it('has no accessibility issues while the combobox list is open', function (): void {
    $page = visit('/');

    $page->type(CUSTOMER, 'grace');
    $page->script(formsSettled('true'));

    $page->keys(CUSTOMER, 'ArrowDown')->assertNoAccessibilityIssues();
});

it('opens the multiselect with the keyboard and toggles options', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("'.TAGS_BUTTON.'").focus()');

    $page->assertScript('document.querySelector("'.TAGS_BUTTON.'").textContent.trim() === "Beta"')
        ->keys(':focus', 'ArrowDown')
        ->assertScript(isHidden(TAGS_LISTBOX).' === false')
        ->assertScript('document.querySelector("'.TAGS_BUTTON.'").getAttribute("aria-expanded") === "true"')
        ->assertScript('document.querySelector("'.TAGS_BUTTON.'").getAttribute("aria-activedescendant") === "ui-tags-option-1"')
        ->keys(':focus', 'ArrowUp')
        ->keys(':focus', 'Space')
        ->assertScript('document.querySelector("'.TAGS_BUTTON.'").textContent.trim() === "Beta, Alpha"')
        ->assertScript('document.getElementById("ui-tags-option-0").getAttribute("aria-selected") === "true"')
        ->assertScript('JSON.stringify('.FORMS_WIRE.'.tags) === JSON.stringify(["b", "a"])')
        ->keys(':focus', 'End')
        ->keys(':focus', 'Enter')
        ->assertScript('document.querySelector("'.TAGS_BUTTON.'").textContent.trim() === "3 selected"')
        ->keys(':focus', 'Enter')
        ->assertScript('document.querySelector("'.TAGS_BUTTON.'").textContent.trim() === "Beta, Alpha"')
        ->keys(':focus', 'Escape')
        ->assertScript(isHidden(TAGS_LISTBOX))
        ->assertScript('document.activeElement.id === "ui-tags"');
});

it('toggles multiselect options with the pointer and closes on an outside click', function (): void {
    $page = visit('/');

    $page->click(TAGS_BUTTON)
        ->click('#ui-tags-option-2')
        ->assertScript('document.getElementById("ui-tags-option-2").getAttribute("aria-selected") === "true"')
        ->assertScript('document.querySelector("'.TAGS_LISTBOX.'").getAttribute("aria-multiselectable") === "true"')
        ->assertScript(isHidden(TAGS_LISTBOX).' === false')
        ->click('#forms-header h1')
        ->assertScript(isHidden(TAGS_LISTBOX));
});

it('has no accessibility issues while the multiselect list is open', function (): void {
    visit('/')->click(TAGS_BUTTON)->assertNoAccessibilityIssues();
});

it('moves between tabs with the arrow keys and shows the matching panel', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("#forms-tabs [role=tab][aria-selected=true]").focus()');

    $page->assertScript('document.querySelectorAll("#forms-tabs [role=tab][tabindex=\\"0\\"]").length === 1')
        ->assertSee('Details panel')
        ->keys(':focus', 'ArrowRight')
        ->assertScript('document.activeElement.textContent.trim() === "Notes"')
        ->assertScript('document.activeElement.getAttribute("aria-selected") === "true"')
        ->assertSee('Notes panel')
        ->assertDontSee('Details panel')
        ->assertScript(FORMS_WIRE.'.tab === "notes"')
        ->keys(':focus', 'End')
        ->assertScript('document.activeElement.textContent.trim() === "Files"')
        ->keys(':focus', 'ArrowRight')
        ->assertScript('document.activeElement.textContent.trim() === "Details"')
        ->keys(':focus', 'ArrowLeft')
        ->assertScript('document.activeElement.textContent.trim() === "Files"')
        ->keys(':focus', 'Home')
        ->assertScript('document.activeElement.textContent.trim() === "Details"')
        ->assertSee('Details panel');
});

it('selects a tab by clicking it', function (): void {
    visit('/')->click('Files')->assertSee('Files panel')->assertDontSee('Details panel')->assertNoAccessibilityIssues();
});

it('opens the dropdown menu onto its first item and moves with the arrow keys, skipping disabled items', function (): void {
    $page = visit('/');

    $page->click(MENU_TRIGGER)
        ->assertScript(isHidden(MENU).' === false')
        ->assertScript('document.activeElement.id === "menu-archive"')
        ->assertScript('document.querySelector("'.MENU_TRIGGER.'").getAttribute("aria-expanded") === "true"')
        ->keys(':focus', 'ArrowDown')
        ->assertScript('document.activeElement.id === "menu-export"')
        ->keys(':focus', 'ArrowDown')
        ->assertScript('document.activeElement.id === "menu-delete"')
        ->keys(':focus', 'ArrowDown')
        ->assertScript('document.activeElement.id === "menu-archive"')
        ->keys(':focus', 'ArrowUp')
        ->assertScript('document.activeElement.id === "menu-delete"')
        ->keys(':focus', 'Home')
        ->assertScript('document.activeElement.id === "menu-archive"')
        ->keys(':focus', 'End')
        ->assertScript('document.activeElement.id === "menu-delete"');
});

it('jumps to an item by its first letter', function (): void {
    $page = visit('/');

    $page->click(MENU_TRIGGER)->assertScript('document.activeElement.id === "menu-archive"')->keys(':focus', 'd')->assertScript('document.activeElement.id === "menu-delete"')
        ->keys(':focus', 'e')->assertScript('document.activeElement.id === "menu-export"');
});

it('closes the dropdown on Escape and returns focus to its button', function (): void {
    $page = visit('/');

    $page->click(MENU_TRIGGER)->assertScript(isHidden(MENU).' === false')
        ->keys(':focus', 'Escape')
        ->assertScript(isHidden(MENU))
        ->assertScript('document.activeElement.id === "forms-menu-trigger"')
        ->assertScript('document.querySelector("'.MENU_TRIGGER.'").getAttribute("aria-expanded") === "false"');
});

it('closes the dropdown on an outside click', function (): void {
    visit('/')->click(MENU_TRIGGER)->assertScript(isHidden(MENU).' === false')
        ->click('#forms-header h1')
        ->assertScript(isHidden(MENU));
});

it('opens the dropdown with the arrow keys from its button', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("'.MENU_TRIGGER.'").focus()');

    $page->keys(':focus', 'ArrowUp')
        ->assertScript('document.activeElement.id === "menu-delete"');
});

it('runs a menu item and closes the menu', function (): void {
    $page = visit('/');

    $page->click(MENU_TRIGGER)->click('#menu-delete')
        ->assertSeeIn('#last-action', 'delete')
        ->assertScript(isHidden(MENU))
        ->assertScript('document.activeElement.id === "forms-menu-trigger"');
});

it('does not run a disabled menu item', function (): void {
    $page = visit('/');

    $page->click(MENU_TRIGGER)
        ->assertScript('document.querySelector("#menu-disabled").disabled === true');
});

it('has no accessibility issues while the dropdown is open', function (): void {
    visit('/')->click(MENU_TRIGGER)->assertNoAccessibilityIssues();
});

it('expands and collapses a section and keeps the server state in step', function (): void {
    $page = visit('/');

    $page->assertScript('document.querySelector("#forms-section button").getAttribute("aria-expanded") === "false"')
        ->assertDontSee('Advanced body')
        ->click('#forms-section button')
        ->assertSee('Advanced body')
        ->assertScript('document.querySelector("#forms-section button").getAttribute("aria-expanded") === "true"')
        ->assertScript(FORMS_WIRE.'.sectionOpen === true')
        ->keys(':focus', 'Enter')
        ->assertDontSee('Advanced body')
        ->assertScript(FORMS_WIRE.'.sectionOpen === false');
});

it('shows each server validation error on its field', function (): void {
    $page = visit('/');

    $page->click('#forms-submit');

    $page->script(formsSettled('true', 1200));

    $page->assertScript('document.getElementById("ui-date-error") !== null')
        ->assertScript('document.getElementById("ui-quantity-error") !== null')
        ->assertScript('document.getElementById("ui-amount-error") !== null')
        ->assertScript('document.getElementById("ui-agreed-error") !== null')
        ->assertScript('document.getElementById("ui-contactMethod-error") !== null')
        ->assertScript('document.getElementById("customer-error") !== null')
        ->assertScript('document.getElementById("ui-attachment-error") !== null')
        ->assertScript('document.getElementById("ui-tags-error") !== null')
        ->assertScript('document.getElementById("ui-tags").getAttribute("aria-invalid") === "true"')
        ->assertScript('document.getElementById("customer").getAttribute("aria-invalid") === "true"')
        ->assertScript('document.querySelector("fieldset").getAttribute("aria-describedby").includes("ui-contactMethod-error")')
        ->assertNoAccessibilityIssues();
});

it('keeps the radio choice, checkbox and number in step with the server', function (): void {
    $page = visit('/');

    $page->click('label[for=ui-contactMethod-2]')
        ->click('label[for=ui-agreed]')
        ->type('#ui-quantity', '12')
        ->assertScript(FORMS_WIRE.'.contactMethod === "post"')
        ->assertScript(FORMS_WIRE.'.agreed === true')
        ->assertScript(FORMS_WIRE.'.quantity === "12"');
});

it('moves between radios with the arrow keys', function (): void {
    $page = visit('/');

    $page->script('document.getElementById("ui-contactMethod-0").focus()');

    $page->keys(':focus', 'ArrowDown')
        ->assertScript('document.activeElement.id === "ui-contactMethod-1"')
        ->assertScript('document.activeElement.checked === true')
        ->assertScript(FORMS_WIRE.'.contactMethod === "phone"');
});

it('describes the currency input by its code', function (): void {
    visit('/')->assertScript('document.getElementById("ui-amount").getAttribute("aria-describedby").split(" ").some((id) => document.getElementById(id).textContent.trim() === "USD")');
});

it('keeps a picked combobox value on the server when the component re-renders', function (): void {
    $page = visit('/');

    $page->type(CUSTOMER, 'turing');
    $page->script(formsSettled('true'));
    $page->click('#customer-option-0')->assertScript(FORMS_WIRE.'.customer === "3"');

    $page->script(formsSettled('true'));
    $page->click('#forms-refresh');
    $page->script(formsSettled('true'));

    $page->assertScript(FORMS_WIRE.'.customer === "3"')
        ->assertScript('document.querySelector("'.CUSTOMER.'").value === "Alan Turing"');
});

it('keeps the multiselect choice on the server when its options change', function (): void {
    $page = visit('/');

    $page->click('#forms-more-tags');
    $page->script(formsSettled('true'));

    $page->assertScript('JSON.stringify('.FORMS_WIRE.'.tags) === JSON.stringify(["b"])')
        ->assertScript('document.querySelector("'.TAGS_BUTTON.'").textContent.trim() === "Beta"')
        ->click(TAGS_BUTTON);

    $page->script('document.getElementById("ui-tags-option-0").click()');

    $page->assertScript('JSON.stringify('.FORMS_WIRE.'.tags) === JSON.stringify(["b", "x"])')
        ->assertScript('document.querySelector("'.TAGS_BUTTON.'").textContent.trim() === "Beta, Xray"');
});

it('keeps the selected tab on the server when the tab list changes', function (): void {
    $page = visit('/');

    $page->click('Files')->assertScript(FORMS_WIRE.'.tab === "files"');
    $page->click('#forms-more-tabs');
    $page->script(formsSettled('true'));

    $page->assertScript(FORMS_WIRE.'.tab === "files"')
        ->assertScript('document.querySelectorAll("#forms-tabs [role=tab]").length === 4')
        ->assertScript('document.querySelector("#forms-tabs [role=tab][aria-selected=true]").textContent.trim() === "Files"')
        ->assertSee('Files panel')
        ->assertDontSee('Details panel');
});

it('marks the keyboard-active combobox and multiselect options with a ring', function (): void {
    $page = visit('/');

    $page->type(CUSTOMER, 'grace');
    $page->script(formsSettled('true'));
    $page->keys(CUSTOMER, 'ArrowDown')
        ->assertScript('document.getElementById("customer-option-0").classList.contains("ring-2")')
        ->assertScript('document.getElementById("customer-option-1").classList.contains("ring-2") === false');

    $page->click(TAGS_BUTTON)
        ->assertScript('document.getElementById("ui-tags-option-1").classList.contains("ring-2")')
        ->assertScript('document.getElementById("ui-tags-option-0").classList.contains("ring-2") === false');
});

it('says so when the combobox search fails', function (): void {
    $page = visit('/');

    $page->script('window.fetch = () => Promise.reject(new TypeError("offline")); true');

    $page->type(CUSTOMER, 'boom');
    $page->script(formsSettled('true', 1500));

    $page->assertNoJavaScriptErrors()
        ->assertSee('Search failed. Try again.')
        ->assertScript('[...document.querySelectorAll("[role=status]")].some((region) => region.textContent.includes("Search failed"))')
        ->assertScript(isHidden(CUSTOMER_LISTBOX));
});

it('waits for the minimum number of characters before searching', function (): void {
    $page = visit('/');

    $page->script('window.requests = 0; const original = window.fetch; window.fetch = (...arguments_) => { if (String(arguments_[0]).includes("livewire")) { window.requests++ } return original(...arguments_) }');

    $page->type('#owner', 'gr');
    $page->script(formsSettled('true'));

    expect($page->script('window.requests'))->toBe(0);

    $page->type('#owner', 'gra');

    expect($page->script(formsSettled(visibleOptions('#owner-listbox'))))->toBe(['Grace Hopper', 'Grace Kelly'])
        ->and($page->script('window.requests'))->toBe(1);
});

it('does not run a disabled menu item when it is activated', function (): void {
    $page = visit('/');

    $page->click(MENU_TRIGGER);
    $page->script('document.getElementById("menu-disabled").click()');
    $page->script(formsSettled('true'));

    $page->assertScript('document.getElementById("menu-disabled").tagName === "BUTTON"')
        ->assertScript('document.getElementById("last-action").textContent === ""');
});

it('selects the first tab when the bound value matches no tab', function (): void {
    $page = visit('/');
    $page->script('new Promise((resolve) => setTimeout(resolve, 500))');

    $page->assertScript('document.querySelector("#blank-tabs [role=tab][aria-selected=true]").textContent.trim() === "One"')
        ->assertScript('document.querySelector("#blank-tabs [role=tab][tabindex=\\"0\\"]") !== null')
        ->assertScript(FORMS_WIRE.'.blankTab === "one"')
        ->assertSee('One panel');
});

it('selects the first tab again when the selected tab is removed', function (): void {
    $page = visit('/');

    $page->click('#forms-more-tabs');
    $page->script(formsSettled('true'));
    $page->click('History')->assertScript(FORMS_WIRE.'.tab === "history"');

    $page->click('#forms-more-tabs');
    $page->script(formsSettled('true'));

    $page->assertScript(FORMS_WIRE.'.tab === "details"')
        ->assertScript('document.querySelector("#forms-tabs [role=tab][aria-selected=true]").textContent.trim() === "Details"')
        ->assertSee('Details panel');
});

it('shows the labels of integer multiselect values', function (): void {
    $page = visit('/');

    $page->assertScript('document.querySelector("#numeric-tags").textContent.trim() === "Alpha, Beta"')
        ->click('#numeric-tags');
    $page->script('document.getElementById("numeric-tags-option-2").click()');

    $page->assertScript('document.querySelector("#numeric-tags").textContent.trim() === "3 selected"');
    $page->script('document.getElementById("numeric-tags-option-0").click()');
    $page->assertScript('document.querySelector("#numeric-tags").textContent.trim() === "Beta, Gamma"');
});
