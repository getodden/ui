<?php

declare(strict_types=1);

const REPEATER_LABELS = 'JSON.stringify([...document.querySelectorAll("#repeater-form li [data-repeater-title]")].map((title) => title.closest("li").querySelector("input[name$=\".label\"]").value))';

function repeaterLabels(): string
{
    return REPEATER_LABELS;
}

function repeaterRow(int $position): string
{
    return '#repeater-form li:nth-child('.$position.')';
}

it('moves a row with the buttons and keeps focus on the button that was used', function (): void {
    $page = visit('/');

    $page->assertScript(repeaterLabels().' === \'["Office","Mobile","Home"]\'')
        ->click(repeaterRow(1).' [data-repeater-action=down]')
        ->assertScript(repeaterLabels().' === \'["Mobile","Office","Home"]\'')
        ->assertScript('document.activeElement.matches("li:nth-child(2) [data-repeater-action=down]")')
        ->assertScript('document.querySelector("[role=status][aria-live=polite]").textContent.includes("position 2 of 3")')
        ->click(repeaterRow(2).' [data-repeater-action=down]')
        ->assertScript('document.activeElement.matches("li:nth-child(3) [data-repeater-action=up]")');
});

it('keeps typed values with their own rows after a reorder', function (): void {
    $page = visit('/');

    $page->fill(repeaterRow(1).' input[name$=".label"]', 'Edited')
        ->click(repeaterRow(1).' [data-repeater-action=down]')
        ->assertScript(repeaterLabels().' === \'["Mobile","Edited","Home"]\'')
        ->click('#repeater-save')
        ->assertScript('document.querySelector("#repeater-state").textContent === \'["Mobile","Edited","Home"]\'');
});

it('adds a row and focuses its first field, then removes it and keeps focus in the list', function (): void {
    $page = visit('/');

    $page->click('[data-repeater-add]')
        ->assertScript('document.querySelectorAll("#repeater-form li").length === 4')
        ->assertScript('document.activeElement.closest("li") === document.querySelector("#repeater-form li:nth-child(4)")')
        ->click(repeaterRow(4).' [data-repeater-action=remove]')
        ->assertScript('document.querySelectorAll("#repeater-form li").length === 3')
        ->wait(0.3)
        ->assertScript('document.querySelector("#repeater-form li:nth-child(3) [data-repeater-row]") === document.activeElement');
});

it('stops adding at the maximum and removing at the minimum', function (): void {
    $page = visit('/');

    $page->click('[data-repeater-add]')
        ->click('[data-repeater-add]')
        ->assertScript('document.querySelectorAll("#repeater-form li").length === 5')
        ->assertScript('document.querySelector("[data-repeater-add]").disabled');

    $page->click(repeaterRow(5).' [data-repeater-action=remove]')
        ->click(repeaterRow(4).' [data-repeater-action=remove]')
        ->click(repeaterRow(3).' [data-repeater-action=remove]')
        ->click(repeaterRow(2).' [data-repeater-action=remove]')
        ->assertScript('document.querySelectorAll("#repeater-form li").length === 1')
        ->assertScript('document.querySelector("#repeater-form [data-repeater-action=remove]").disabled');
});

it('shows a validation error on the row it belongs to', function (): void {
    $page = visit('/');

    $page->fill(repeaterRow(2).' input[name$=".label"]', '')
        ->click('#repeater-save')
        ->assertScript('document.querySelectorAll("#repeater-form li:nth-child(2) [aria-invalid=true]").length === 1')
        ->assertScript('document.querySelectorAll("#repeater-form li:nth-child(1) [aria-invalid=true]").length === 0')
        ->assertScript('document.querySelector("#repeater-saved") === null');
});

it('reorders by dragging the handle of a row onto another', function (): void {
    $page = visit('/');

    $page->script('(() => { const handle = document.querySelector("#repeater-form li:nth-child(1) [draggable=true]"); const target = document.querySelector("#repeater-form li:nth-child(3)"); const transfer = new DataTransfer(); handle.dispatchEvent(new DragEvent("dragstart", { bubbles: true, dataTransfer: transfer })); target.dispatchEvent(new DragEvent("dragover", { bubbles: true, cancelable: true, dataTransfer: transfer })); target.dispatchEvent(new DragEvent("drop", { bubbles: true, cancelable: true, dataTransfer: transfer })); })()');

    $page->assertScript(repeaterLabels().' === \'["Mobile","Home","Office"]\'');
});
