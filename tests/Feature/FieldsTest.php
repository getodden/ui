<?php

declare(strict_types=1);

use Dom\Element;
use Illuminate\View\ViewException;
use Odden\Ui\Tests\Support\Html;

function describedBy(Element $control): array
{
    $value = $control->getAttribute('aria-describedby');

    return $value === null ? [] : explode(' ', $value);
}

it('labels the control and derives name and id from wire:model', function (): void {
    view()->share('errors', Html::errors(['form.email' => 'The email is invalid.']));
    $dom = Html::render('<x-ui::input label="Email address" wire:model.live="form.email" />');

    $input = $dom->querySelector('input');

    expect($input->getAttribute('name'))->toBe('form.email')
        ->and($input->getAttribute('id'))->toBe('ui-form-email')
        ->and($dom->querySelector('label')->getAttribute('for'))->toBe($input->getAttribute('id'))
        ->and($input->hasAttribute('wire:model.live'))->toBeTrue();
});

it('turns a bracketed name into a valid id', function (): void {
    $dom = Html::render('<x-ui::input label="City" name="address[city]" />');

    expect($dom->querySelector('input')->getAttribute('id'))->toBe('ui-address-city');
});

it('uses a given id over a derived one', function (): void {
    $dom = Html::render('<x-ui::input label="City" name="city" id="my-city" />');

    expect($dom->querySelector('label')->getAttribute('for'))->toBe('my-city')
        ->and($dom->querySelector('input')->getAttribute('id'))->toBe('my-city');
});

it('describes the control by exactly the hint and error that are rendered', function (string $attributes, array $suffixes): void {
    $dom = Html::render("<x-ui::input label=\"Name\" name=\"name\" {$attributes} />");

    $input = $dom->querySelector('input');
    $expected = array_map(fn (string $suffix): string => "ui-name-{$suffix}", $suffixes);

    expect(describedBy($input))->toBe($expected);

    foreach ($expected as $id) {
        expect($dom->getElementById($id))->not->toBeNull();
    }
})->with([
    'nothing' => ['', []],
    'hint only' => ['hint="Shown on invoices"', ['hint']],
    'error only' => ['error="Too short"', ['error']],
    'both' => ['hint="Shown on invoices" error="Too short"', ['hint', 'error']],
]);

it('appends a given aria-describedby to the hint and error ids', function (): void {
    $dom = Html::render('<x-ui::input label="Name" name="name" hint="Shown" error="Short" aria-describedby="extra-help" />');

    expect(describedBy($dom->querySelector('input')))->toBe(['ui-name-hint', 'ui-name-error', 'extra-help']);
});

it('keeps a given aria-describedby when there is no hint or error', function (): void {
    $dom = Html::render('<x-ui::input label="Name" name="name" aria-describedby="extra-help" />');

    expect(describedBy($dom->querySelector('input')))->toBe(['extra-help']);
});

it('refuses a field without a label', function (string $component): void {
    Html::render("<{$component} name=\"name\" />");
})->throws(ViewException::class, 'needs a label')->with(['x-ui::input', 'x-ui::select', 'x-ui::textarea', 'x-ui::toggle']);

it('accepts a field named by aria-label or aria-labelledby instead of a label', function (string $attribute): void {
    $dom = Html::render("<x-ui::input name=\"search\" {$attribute}=\"Search\" />");

    expect($dom->querySelector('input')->getAttribute($attribute))->toBe('Search')
        ->and($dom->querySelector('label'))->toBeNull();
})->with(['aria-label', 'aria-labelledby']);

it('marks the control invalid only when there is an error', function (): void {
    $valid = Html::render('<x-ui::input label="Name" name="name" />')->querySelector('input');
    $invalid = Html::render('<x-ui::input label="Name" name="name" error="Too short" />')->querySelector('input');

    expect($valid->hasAttribute('aria-invalid'))->toBeFalse()
        ->and($invalid->getAttribute('aria-invalid'))->toBe('true');
});

it('reads the error from the shared errors bag by the wire:model key', function (): void {
    view()->share('errors', Html::errors(['form.email' => 'The email is invalid.']));
    $dom = Html::render('<x-ui::input label="Email" wire:model="form.email" />');

    $input = $dom->querySelector('input');

    expect($input->getAttribute('aria-invalid'))->toBe('true')
        ->and($dom->getElementById('ui-form-email-error')->textContent)->toBe('The email is invalid.');
});

it('reads the error for a bracketed name by its dotted key', function (): void {
    view()->share('errors', Html::errors(['address.city' => 'Required.']));
    $dom = Html::render('<x-ui::input label="City" name="address[city]" />');

    expect($dom->getElementById('ui-address-city-error')->textContent)->toBe('Required.');
});

it('prefers an explicit error over the errors bag', function (): void {
    view()->share('errors', Html::errors(['email' => 'Theirs']));
    $dom = Html::render('<x-ui::input label="Email" name="email" error="Mine" />');

    expect($dom->getElementById('ui-email-error')->textContent)->toBe('Mine');
});

it('renders without an errors bag', function (): void {
    $dom = Html::render('<x-ui::input label="Email" name="email" />');

    expect($dom->querySelector('input')->hasAttribute('aria-invalid'))->toBeFalse();
});

it('requires the control and hides the visible mark from assistive technology', function (): void {
    $dom = Html::render('<x-ui::input label="Name" name="name" required />');

    $mark = $dom->querySelector('label span');

    expect($dom->querySelector('input')->hasAttribute('required'))->toBeTrue()
        ->and($mark->getAttribute('aria-hidden'))->toBe('true')
        ->and($mark->textContent)->toBe('*');
});

it('shows no required mark on an optional field', function (): void {
    $dom = Html::render('<x-ui::input label="Name" name="name" />');

    expect($dom->querySelector('label span'))->toBeNull()
        ->and($dom->querySelector('input')->hasAttribute('required'))->toBeFalse();
});

it('renders a textarea with its content and the same contract', function (): void {
    $dom = Html::render('<x-ui::textarea label="Notes" name="notes" hint="Private" error="Too long">Hello</x-ui::textarea>');

    $textarea = $dom->querySelector('textarea');

    expect($textarea->textContent)->toBe('Hello')
        ->and($dom->querySelector('label')->getAttribute('for'))->toBe($textarea->getAttribute('id'))
        ->and(describedBy($textarea))->toBe(['ui-notes-hint', 'ui-notes-error'])
        ->and($textarea->getAttribute('aria-invalid'))->toBe('true');
});

it('renders select options, a placeholder and the current value', function (): void {
    $dom = Html::render('<x-ui::select label="Plan" name="plan" :options="[\'a\' => \'Alpha\', \'b\' => \'Beta\']" placeholder="Choose" value="b" />');

    $options = $dom->querySelectorAll('select option');

    expect($options)->toHaveCount(3)
        ->and($options[0]->getAttribute('value'))->toBe('')
        ->and($options[0]->textContent)->toBe('Choose')
        ->and($options[2]->hasAttribute('selected'))->toBeTrue()
        ->and($options[1]->hasAttribute('selected'))->toBeFalse()
        ->and($dom->querySelector('label')->getAttribute('for'))->toBe($dom->querySelector('select')->getAttribute('id'));
});

it('renders select options from the slot', function (): void {
    $dom = Html::render('<x-ui::select label="Plan" name="plan"><option value="x">Ex</option></x-ui::select>');

    expect($dom->querySelectorAll('select option'))->toHaveCount(1);
});

it('renders a toggle as a labelled switch', function (): void {
    $dom = Html::render('<x-ui::toggle label="Notify me" wire:model="notify" hint="By email" />');

    $switch = $dom->querySelector('input[type=checkbox]');

    expect($switch->getAttribute('role'))->toBe('switch')
        ->and($dom->querySelector('label')->getAttribute('for'))->toBe($switch->getAttribute('id'))
        ->and($dom->querySelector('label')->textContent)->toBe('Notify me')
        ->and(describedBy($switch))->toBe(['ui-notify-hint']);
});
