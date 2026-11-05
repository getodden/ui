<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Livewire\Livewire;
use Odden\Ui\Tests\Support\EditorComponent;
use Odden\Ui\Tests\Support\Html;

function escapedForAttribute(string $json): string
{
    return str_replace('"', chr(92).'u0022', $json);
}

function editorConfig(string $attributes = ''): string
{
    return Html::render("<x-ui::editor label=\"Body\" wire:model=\"body\" {$attributes} />")->querySelector('[x-data]')->getAttribute('x-data');
}

it('labels the editor and describes it by the hint and error that are rendered', function (): void {
    $dom = Html::render('<x-ui::editor label="Body" wire:model="form.body" hint="Shown to everyone" error="Too short" required />');

    $label = $dom->getElementById('ui-form-body-label');
    $meta = $dom->querySelector('[data-class]');

    expect($label->textContent)->toContain('Body')
        ->and($meta->getAttribute('data-labelledby'))->toBe('ui-form-body-label')
        ->and($meta->getAttribute('data-describedby'))->toBe('ui-form-body-hint ui-form-body-error')
        ->and($meta->hasAttribute('data-invalid'))->toBeTrue()
        ->and($meta->hasAttribute('data-required'))->toBeTrue()
        ->and($dom->getElementById('ui-form-body-hint')->textContent)->toBe('Shown to everyone')
        ->and($dom->getElementById('ui-form-body-error')->textContent)->toBe('Too short')
        ->and($label->querySelector('span')->getAttribute('aria-hidden'))->toBe('true');
});

it('leaves out the invalid and required markers when there is nothing to report', function (): void {
    $meta = Html::render('<x-ui::editor label="Body" name="body" />')->querySelector('[data-class]');

    expect($meta->hasAttribute('data-invalid'))->toBeFalse()
        ->and($meta->hasAttribute('data-required'))->toBeFalse()
        ->and($meta->hasAttribute('data-describedby'))->toBeFalse();
});

it('reads the error from the errors bag by the wire:model key', function (): void {
    view()->share('errors', Html::errors(['form.body' => 'The body is required.']));
    $dom = Html::render('<x-ui::editor label="Body" wire:model="form.body" />');

    expect($dom->getElementById('ui-form-body-error')->textContent)->toBe('The body is required.')
        ->and($dom->querySelector('[data-class]')->hasAttribute('data-invalid'))->toBeTrue();
});

it('refuses an editor without a label', function (): void {
    Html::render('<x-ui::editor name="body" />');
})->throws(ViewException::class, 'needs a label');

it('names the editor by aria-label or aria-labelledby instead of a label', function (string $attribute, string $meta): void {
    $dom = Html::render("<x-ui::editor name=\"body\" {$attribute}=\"Notes\" />");

    expect($dom->querySelector('[data-class]')->getAttribute($meta))->toBe('Notes')
        ->and($dom->querySelector('[id$=-label]'))->toBeNull();
})->with([
    'aria-label' => ['aria-label', 'data-label'],
    'aria-labelledby' => ['aria-labelledby', 'data-labelledby'],
]);

it('refuses a format that is neither html nor json', function (): void {
    Html::render('<x-ui::editor label="Body" name="body" format="markdown" />');
})->throws(ViewException::class, 'html or json');

it('refuses a mention-search that is not a method name', function (string $value): void {
    Html::render("<x-ui::editor label=\"Body\" name=\"body\" mention-search=\"{$value}\" />");
})->throws(ViewException::class, 'name of a public method')->with(['a call' => 'search()', 'a path' => 'a.b', 'spaces' => 'a b']);

it('renders the full toolbar by default and ties it to the editable region', function (): void {
    $dom = Html::render('<x-ui::editor label="Body" name="body" />');

    $toolbar = $dom->querySelector('[role=toolbar]');
    $tools = array_map(fn ($button): string => $button->getAttribute('data-tool'), iterator_to_array($dom->querySelectorAll('[role=toolbar] button')));

    expect($toolbar->getAttribute('aria-label'))->toBe('Formatting for Body')
        ->and($toolbar->getAttribute('aria-controls'))->toBe('ui-body')
        ->and($tools)->toBe(['bold', 'italic', 'link', 'bullet-list', 'ordered-list', 'heading-2', 'heading-3'])
        ->and($dom->querySelectorAll('[role=toolbar] button[tabindex="0"]'))->toHaveCount(1)
        ->and($dom->querySelector('[data-tool=bold]')->getAttribute('aria-label'))->toBe('Bold')
        ->and($dom->querySelector('[data-tool=heading-2]')->getAttribute('aria-label'))->toBe('Heading 2')
        ->and($dom->querySelector('[data-tool=heading-2]')->textContent)->toContain('H2');
});

it('renders only the tools asked for, in the order given, and ignores unknown ones', function (): void {
    $dom = Html::render('<x-ui::editor label="Body" name="body" :toolbar="[\'italic\', \'nope\', \'bold\']" />');

    $tools = array_map(fn ($button): string => $button->getAttribute('data-tool'), iterator_to_array($dom->querySelectorAll('[role=toolbar] button')));

    expect($tools)->toBe(['italic', 'bold'])
        ->and($dom->querySelector('[data-link-apply]'))->toBeNull();
});

it('renders no toolbar when the toolbar is empty', function (): void {
    $dom = Html::render('<x-ui::editor label="Body" name="body" :toolbar="[]" />');

    expect($dom->querySelector('[role=toolbar]'))->toBeNull()
        ->and($dom->querySelector('[data-link-apply]'))->toBeNull();
});

it('renders the inline link form with its own labelled input', function (): void {
    $dom = Html::render('<x-ui::editor label="Body" name="body" />');

    $input = $dom->getElementById('ui-body-link-address');

    expect($input->getAttribute('x-model'))->toBe('link.url')
        ->and($dom->querySelector('label[for=ui-body-link-address]')->textContent)->toBe('Link address')
        ->and($dom->querySelector('[data-link-apply]'))->not->toBeNull()
        ->and($dom->querySelector('[data-link-cancel]'))->not->toBeNull();
});

it('entangles the property and keeps wire:model off the markup', function (string $modifier, string $live, string $blur): void {
    $dom = Html::render("<x-ui::editor label=\"Body\" wire:model{$modifier}=\"body\" />");
    $config = $dom->querySelector('[x-data]')->getAttribute('x-data');

    expect($config)->toContain('$wire.$entangle(')
        ->and($config)->toContain("body', {$live})")
        ->and($config)->toContain(escapedForAttribute('"commitOnBlur":'.$blur))
        ->and($dom->querySelector('[wire\:model]'))->toBeNull()
        ->and($dom->querySelector('[wire\:model\.live]'))->toBeNull()
        ->and($dom->querySelector('[wire\:model\.blur]'))->toBeNull();
})->with([
    'deferred' => ['', 'false', 'false'],
    'live' => ['.live', 'true', 'false'],
    'blur' => ['.blur', 'false', 'true'],
]);

it('passes the format, mentions and search method to the script', function (): void {
    $config = editorConfig('format="json" :mentions="[[\'id\' => 1, \'label\' => \'Ada\']]" mention-char="#"');

    expect($config)->toContain(escapedForAttribute('"format":"json"'))
        ->and($config)->toContain(escapedForAttribute('"label":"Ada"'))
        ->and($config)->toContain(escapedForAttribute('"mentionChar":"#"'))
        ->and(editorConfig('mention-search="searchPeople"'))->toContain(escapedForAttribute('"mentionSearch":"searchPeople"'));
});

it('renders the suggestion listbox only when mentions are enabled', function (): void {
    $without = Html::render('<x-ui::editor label="Body" name="body" />');
    $with = Html::render('<x-ui::editor label="Body" name="body" :mentions="[]" />');

    expect($without->querySelector('[role=listbox]'))->toBeNull()
        ->and($with->querySelector('[role=listbox]')->getAttribute('id'))->toBe('ui-body-mentions')
        ->and($with->querySelector('[role=listbox]')->getAttribute('aria-label'))->toBe('Suggestions');
});

it('never prints the bound value into the HTML', function (): void {
    $payload = '<script>alert("NEEDLE")</script> "quoted" NEEDLE';

    $html = Livewire::test(EditorComponent::class)->set('body', $payload)->html();
    $withoutSnapshot = preg_replace('/wire:snapshot="[^"]*"/', '', $html);

    expect($withoutSnapshot)->not->toContain('NEEDLE')
        ->and($html)->not->toContain('<script>alert')
        ->and($html)->not->toContain('alert("NEEDLE")');
});

it('keeps the editable region out of Livewire morphing', function (): void {
    $dom = Html::render('<x-ui::editor label="Body" name="body" />');

    expect($dom->querySelector('[x-ref=host]')->parentNode->hasAttribute('wire:ignore'))->toBeTrue();
});
