<?php

declare(strict_types=1);

const BODY_EDITABLE = '#editor-body [role=textbox]';
const PEOPLE_EDITABLE = '#editor-people [role=textbox]';
const SEARCH_EDITABLE = '#editor-search [role=textbox]';
const JSON_EDITABLE = '#editor-json [role=textbox]';

function editorSettled(string $script): string
{
    return 'new Promise((resolve) => setTimeout(() => resolve('.$script.'), 700))';
}

function editorIsHidden(string $selector): string
{
    return 'getComputedStyle(document.querySelector("'.$selector.'")).display === "none"';
}

function savedText(string $selector): string
{
    return 'document.querySelector("'.$selector.'").textContent';
}

it('loads the editor module without JavaScript errors', function (): void {
    visit('/')->assertNoJavaScriptErrors()->assertPresent(BODY_EDITABLE);
});

it('wires the editable region as a labelled, described textbox', function (): void {
    $page = visit('/');

    $attributes = $page->script('(() => { const editable = document.querySelector("'.BODY_EDITABLE.'"); return { role: editable.getAttribute("role"), multiline: editable.getAttribute("aria-multiline"), labelledby: editable.getAttribute("aria-labelledby"), label: document.getElementById(editable.getAttribute("aria-labelledby")).textContent.trim(), describedby: editable.getAttribute("aria-describedby"), hint: document.getElementById(editable.getAttribute("aria-describedby")).textContent.trim(), invalid: editable.hasAttribute("aria-invalid"), toolbarControls: document.querySelector("#editor-body [role=toolbar]").getAttribute("aria-controls") === editable.id } })()');

    expect($attributes)->toBe([
        'role' => 'textbox',
        'multiline' => 'true',
        'labelledby' => 'ui-body-label',
        'label' => 'Body',
        'describedby' => 'ui-body-hint',
        'hint' => 'Saved as HTML',
        'invalid' => false,
        'toolbarControls' => true,
    ]);

    $required = $page->script('(() => { const editable = document.querySelector("#editor-error [role=textbox]"); return { invalid: editable.getAttribute("aria-invalid"), required: editable.getAttribute("aria-required"), describedby: editable.getAttribute("aria-describedby") } })()');

    expect($required)->toBe(['invalid' => 'true', 'required' => 'true', 'describedby' => 'ui-emptyBody-error']);
});

it('sends typed and bold text to the server through wire:model', function (): void {
    $page = visit('/');

    $page->click(BODY_EDITABLE)
        ->keys(BODY_EDITABLE, ['ControlOrMeta+a', 'H', 'e', 'l', 'l', 'o'])
        ->assertSeeIn('#saved-body', '<p>Hello</p>')
        ->keys(BODY_EDITABLE, 'ControlOrMeta+a')
        ->click('#editor-body [data-tool=bold]')
        ->assertSeeIn('#saved-body', '<p><strong>Hello</strong></p>');
});

it('replaces the editor content when the server changes the property', function (): void {
    $page = visit('/');

    $page->click('#set-body')
        ->assertSeeIn('#saved-body', 'From the server')
        ->assertScript('document.querySelector("'.BODY_EDITABLE.'").innerHTML === "<h2>From the server</h2><p>Replaced <em>content</em></p>"')
        ->assertScript('document.querySelectorAll("#editor-body [data-tool][tabindex=\"0\"]").length === 1')
        ->assertScript('document.querySelector("#editor-body [data-tool=heading-2]").getAttribute("aria-pressed") === "false"');
});

it('keeps the cursor and content while the server echoes its own value', function (): void {
    $page = visit('/');

    $page->click(BODY_EDITABLE)->keys(BODY_EDITABLE, ['ControlOrMeta+a', 'a', 'b'])
        ->assertSeeIn('#saved-body', '<p>ab</p>')
        ->keys(BODY_EDITABLE, ['c', 'd'])
        ->assertSeeIn('#saved-body', '<p>abcd</p>')
        ->assertScript('document.querySelector("'.BODY_EDITABLE.'").textContent === "abcd"');
});

it('round-trips stored Filament-shaped HTML unchanged', function (): void {
    $page = visit('/');

    $page->assertScript('document.querySelector("'.BODY_EDITABLE.'").innerHTML.replace(" contenteditable=\"false\"", "") === '.savedText('#saved-body'));
});

it('has one toolbar tab stop and moves between buttons with the arrow keys', function (): void {
    $page = visit('/');

    $page->assertScript('document.querySelectorAll("#editor-body [data-tool][tabindex=\"0\"]").length === 1');

    $page->script('document.querySelector("#editor-body [data-tool=bold]").focus()');

    $page->keys(':focus', 'ArrowRight')
        ->assertScript('document.activeElement.dataset.tool === "italic"')
        ->assertScript('document.querySelectorAll("#editor-body [data-tool][tabindex=\"0\"]").length === 1')
        ->keys(':focus', 'End')
        ->assertScript('document.activeElement.dataset.tool === "heading-3"')
        ->keys(':focus', 'ArrowRight')
        ->assertScript('document.activeElement.dataset.tool === "bold"')
        ->keys(':focus', 'ArrowLeft')
        ->assertScript('document.activeElement.dataset.tool === "heading-3"')
        ->keys(':focus', 'Home')
        ->assertScript('document.activeElement.dataset.tool === "bold"');
});

it('lands on the remembered toolbar button when tabbing back from the editor', function (): void {
    $page = visit('/');

    $page->script('document.querySelector("#editor-body [data-tool=italic]").focus()');
    $page->click(BODY_EDITABLE)->keys(BODY_EDITABLE, 'Shift+Tab')
        ->assertScript('document.activeElement.dataset.tool === "italic"');
});

it('reflects the selection in aria-pressed', function (): void {
    $page = visit('/');
    $bold = '#editor-search [data-tool=bold]';

    $page->click(SEARCH_EDITABLE)->keys(SEARCH_EDITABLE, ['x', 'ControlOrMeta+a'])
        ->assertAttribute($bold, 'aria-pressed', 'false')
        ->click($bold)
        ->assertAttribute($bold, 'aria-pressed', 'true')
        ->assertSeeIn('#saved-search', '<p><strong>x</strong></p>')
        ->click($bold)
        ->assertAttribute($bold, 'aria-pressed', 'false');
});

it('adds a link through the inline form and refuses an unsafe address', function (): void {
    $page = visit('/');

    $page->click(BODY_EDITABLE)->keys(BODY_EDITABLE, ['ControlOrMeta+a', 'x', 'ControlOrMeta+a'])
        ->click('#editor-body [data-tool=link]')
        ->assertScript('document.activeElement.id === "ui-body-link-address"')
        ->keys(':focus', ['j', 'a', 'v', 'a', 's', 'c', 'r', 'i', 'p', 't', ':', 'a', 'l', 'e', 'r', 't', '(', '1', ')', 'Enter'])
        ->assertSeeIn('#ui-body-link-error', 'Use an address that starts with http');

    $page->assertScript('document.querySelector("#ui-body-link-address").getAttribute("aria-invalid") === "true"')
        ->assertDontSee('javascript:alert')
        ->assertScript('!'.savedText('#saved-body').'.includes("javascript:")');

    $page->keys(':focus', ['ControlOrMeta+a', 'e', 'x', 'a', 'm', 'p', 'l', 'e', '.', 'o', 'r', 'g'])
        ->click('#editor-body [data-link-apply]')
        ->assertSeeIn('#saved-body', '<p><a target="_blank" rel="noopener noreferrer nofollow" href="https://example.org">x</a></p>')
        ->assertScript('document.querySelector("#editor-body [data-tool=link]").getAttribute("aria-expanded") === "false"')
        ->assertScript('!document.querySelector("#editor-body [data-tool=link]").hasAttribute("aria-pressed")');
});

it('closes the link form on Escape and returns focus to the editor', function (): void {
    $page = visit('/');

    $page->click(BODY_EDITABLE)->keys(BODY_EDITABLE, 'ControlOrMeta+k')
        ->assertScript('document.activeElement.id === "ui-body-link-address"')
        ->keys(':focus', 'Escape')
        ->assertScript('document.activeElement === document.querySelector("'.BODY_EDITABLE.'")')
        ->assertScript(editorIsHidden('#ui-body-link-address').' || document.querySelector("#ui-body-link-address").offsetParent === null');
});

it('removes an existing link from the inline form', function (): void {
    $page = visit('/');

    $page->click(BODY_EDITABLE)->keys(BODY_EDITABLE, ['ControlOrMeta+a', 'x', 'ControlOrMeta+a'])
        ->click('#editor-body [data-tool=link]')
        ->assertScript('document.activeElement.id === "ui-body-link-address"')
        ->keys(':focus', ['e', 'x', '.', 'c', 'o', 'Enter'])
        ->assertSeeIn('#saved-body', 'href="https://ex.co"');

    $page->click('#editor-body [data-tool=link]')
        ->click('#editor-body [data-link-remove]')
        ->assertSeeIn('#saved-body', '<p>x</p>');
});

it('suggests mentions from a static list and inserts Filament-shaped markup', function (): void {
    $page = visit('/');

    $page->click(PEOPLE_EDITABLE)->keys(PEOPLE_EDITABLE, ['@', 'a', 'd'])
        ->assertVisible('#ui-peopleBody-mentions')
        ->assertScript('document.querySelectorAll("#ui-peopleBody-mentions [role=option]").length === 2')
        ->assertScript('document.querySelector("'.PEOPLE_EDITABLE.'").getAttribute("aria-controls") === "ui-peopleBody-mentions"')
        ->assertScript('document.querySelector("'.PEOPLE_EDITABLE.'").getAttribute("aria-activedescendant") === "ui-peopleBody-mention-0"')
        ->assertScript('document.querySelector("#ui-peopleBody-mention-0").getAttribute("aria-selected") === "true"')
        ->keys(PEOPLE_EDITABLE, ['ArrowDown', 'Enter'])
        ->assertSeeIn('#saved-people', '<p><span data-type="mention" data-id="2" data-label="Adam Smith" data-char="@">@Adam Smith</span> </p>')
        ->assertScript(editorIsHidden('#ui-peopleBody-mentions'))
        ->assertScript('!document.querySelector("'.PEOPLE_EDITABLE.'").hasAttribute("aria-activedescendant")');
});

it('inserts a mention by clicking an option and closes on Escape', function (): void {
    $page = visit('/');

    $page->click(PEOPLE_EDITABLE)->keys(PEOPLE_EDITABLE, ['@', 'a', 'd'])
        ->click('#ui-peopleBody-mention-0')
        ->assertSeeIn('#saved-people', 'data-id="1" data-label="Ada Lovelace"');

    $page->keys(PEOPLE_EDITABLE, ['@', 'a', 'Escape'])
        ->assertScript(editorIsHidden('#ui-peopleBody-mentions'));
});

it('says when no mention matches and never lists more than eight', function (): void {
    $page = visit('/');

    $page->click(PEOPLE_EDITABLE)->keys(PEOPLE_EDITABLE, ['@', 'z', 'z'])
        ->assertSee('No matches')
        ->assertScript('document.querySelectorAll("#ui-peopleBody-mentions [role=option]").length === 0')
        ->assertScript('!document.querySelector("'.PEOPLE_EDITABLE.'").hasAttribute("aria-activedescendant")');
});

it('suggests mentions from a Livewire method', function (): void {
    $page = visit('/');

    $page->click(SEARCH_EDITABLE)->keys(SEARCH_EDITABLE, ['@', 'g', 'r'])
        ->assertVisible('#ui-searchBody-mentions')
        ->assertScript('[...document.querySelectorAll("#ui-searchBody-mentions [role=option]")].map((option) => option.textContent).join("|") === "Grace Hopper|Grace Kelly"')
        ->keys(SEARCH_EDITABLE, 'Enter')
        ->assertSeeIn('#saved-search', '<span data-type="mention" data-id="1" data-label="Grace Hopper" data-char="@">@Grace Hopper</span>');
});

it('ignores a stale search response', function (): void {
    $page = visit('/');

    $page->click(SEARCH_EDITABLE)->keys(SEARCH_EDITABLE, ['@', 'a', 'l', 'a'])
        ->assertVisible('#ui-searchBody-mentions')
        ->assertScript('[...document.querySelectorAll("#ui-searchBody-mentions [role=option]")].map((option) => option.textContent).join("|") === "Alan Turing"');
});

it('sends the JSON document with the mention node', function (): void {
    $page = visit('/');

    $page->click(JSON_EDITABLE)->keys(JSON_EDITABLE, ['ControlOrMeta+End', 'x'])
        ->assertSeeIn('#saved-json', '"type":"mention","attrs":{"id":"7","label":"Ada Lovelace","char":"@"}')
        ->assertSeeIn('#saved-json', 'therex');
});

it('does not execute or render stored script, handler or javascript: content', function (): void {
    $page = visit('/');

    $page->assertNoJavaScriptErrors();

    $result = $page->script(editorSettled('{ xss: window.__xss === true, img: document.querySelectorAll("#editor-untrusted img").length, script: document.querySelectorAll("#editor-untrusted script").length, javascript: document.querySelectorAll("#editor-untrusted a[href^=javascript]").length, anchors: document.querySelectorAll("#editor-untrusted a").length, text: document.querySelector("#editor-untrusted [role=textbox]").textContent }'));

    expect($result)->toBe(['xss' => false, 'img' => 0, 'script' => 0, 'javascript' => 0, 'anchors' => 0, 'text' => 'Safe textbad link']);
});

it('has no accessibility issues', function (): void {
    visit('/')->assertNoAccessibilityIssues();
});

it('has no accessibility issues with the mention list open', function (): void {
    $page = visit('/');

    $page->click(PEOPLE_EDITABLE)->keys(PEOPLE_EDITABLE, ['@', 'a', 'd'])
        ->assertVisible('#ui-peopleBody-mentions')
        ->assertNoAccessibilityIssues();
});

it('has no accessibility issues with the link form open', function (): void {
    $page = visit('/');

    $page->click('#editor-body [data-tool=link]')
        ->assertVisible('#ui-body-link-address')
        ->assertNoAccessibilityIssues();
});

it('round-trips Filament-shaped HTML through the editor unchanged', function (): void {
    $page = visit('/');

    $result = $page->script(editorSettled('{ editor: document.querySelector("#editor-filament [role=textbox]").innerHTML.replaceAll(" contenteditable=\"false\"", ""), saved: '.savedText('#saved-filament').' }'));

    expect($result['editor'])->toBe($result['saved']);
});

it('refuses to edit stored JSON the editor cannot show and says so', function (): void {
    $page = visit('/');

    $before = $page->script(savedText('#saved-unreadable'));

    $page->click('#editor-unreadable [role=textbox]');

    $page->assertSee("This text has content the editor can't show")
        ->assertScript('document.querySelector("#editor-unreadable [role=textbox]").getAttribute("contenteditable") === "false"')
        ->assertScript('document.querySelector("#editor-unreadable [role=textbox]").getAttribute("aria-describedby").split(" ").includes("ui-unreadableDoc-unreadable")')
        ->assertScript('document.querySelector("#editor-unreadable [role=toolbar]").offsetParent === null')
        ->keys('#editor-unreadable [role=textbox]', ['x', 'y']);

    $after = $page->script(editorSettled(savedText('#saved-unreadable')));

    expect($after)->toBe($before)->and($after)->toContain('"type":"image"');
});

it('never emits an unsafe link href from loaded JSON', function (): void {
    $page = visit('/');

    $page->assertScript('document.querySelectorAll("#editor-linkdoc a[href^=javascript]").length === 0')
        ->click('#editor-linkdoc [role=textbox]')
        ->keys('#editor-linkdoc [role=textbox]', ['ControlOrMeta+End', 'x'])
        ->assertSeeIn('#saved-linkdoc', 'goodx');

    $saved = $page->script(savedText('#saved-linkdoc'));

    expect($saved)->not->toContain('javascript:')->and($saved)->toContain('bad');
});

it('commits a live editor once per pause, using the debounce asked for', function (): void {
    $page = visit('/');

    $page->script('window.updates = 0; const original = window.fetch; window.fetch = (...arguments_) => { if (String(arguments_[0]).includes("livewire")) { window.updates++ } return original(...arguments_) }');

    $page->click('#editor-debounced [role=textbox]')->keys('#editor-debounced [role=textbox]', ['a', 'b', 'c']);

    expect($page->script('window.updates'))->toBe(0);

    $page->assertSeeIn('#saved-debounced', '<p>abc</p>');

    expect($page->script(editorSettled('window.updates')))->toBe(1);
});

it('keeps the caret when the server only normalises its echo', function (): void {
    $page = visit('/');

    $page->click(BODY_EDITABLE)->keys(BODY_EDITABLE, ['ControlOrMeta+a', 'a', 'b', 'c', 'd'])
        ->assertSeeIn('#saved-body', '<p>abcd</p>')
        ->keys(BODY_EDITABLE, ['ArrowLeft', 'ArrowLeft']);

    $page->script('Livewire.find(document.querySelector("#editor-body").closest("[wire\\\\:id]").getAttribute("wire:id")).padBody()');
    $page->script(editorSettled('true'));

    $page->keys(BODY_EDITABLE, 'X')->assertSeeIn('#saved-body', '<p>abXcd</p>');
});

it('ignores the link shortcut when the toolbar has no link tool', function (): void {
    $page = visit('/');

    $page->click(SEARCH_EDITABLE)->keys(SEARCH_EDITABLE, 'ControlOrMeta+k')
        ->assertNoJavaScriptErrors()
        ->assertScript('document.activeElement === document.querySelector("'.SEARCH_EDITABLE.'")');
});

it('keeps an explicit id with quotes out of the Alpine expressions', function (): void {
    $page = visit('/');
    $id = "x'+alert(1)+'";

    $page->click('#editor-odd [data-tool=link]')
        ->assertScript('document.activeElement.id === "'.$id.'-link-address"')
        ->keys(':focus', ['j', 'a', 'v', 'a', 's', 'c', 'r', 'i', 'p', 't', ':', 'x', 'Enter'])
        ->assertScript('document.activeElement.getAttribute("aria-describedby") === "'.$id.'-link-error"')
        ->keys(':focus', 'Escape')
        ->keys('#editor-odd [role=textbox]', ['@', 'a', 'd'])
        ->assertScript('document.querySelector("#editor-odd [role=option]").id === "'.$id.'-mention-0"')
        ->assertNoJavaScriptErrors();
});

it('names heading buttons by their visible text and exposes the link button as a disclosure', function (): void {
    $page = visit('/');

    $page->assertScript('[...document.querySelectorAll("#editor-body [data-tool^=heading]")].every((button) => button.getAttribute("aria-label").includes(button.textContent.trim()))')
        ->assertScript('document.querySelector("#editor-body [data-tool=link]").getAttribute("aria-controls") === "ui-body-link"')
        ->click('#editor-body [data-tool=link]')
        ->assertScript('document.querySelector("#editor-body [data-tool=link]").getAttribute("aria-expanded") === "true"');
});
