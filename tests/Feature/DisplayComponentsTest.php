<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Odden\Ui\Tests\Support\Html;

it('renders a button as a link when it has an href', function (): void {
    $dom = Html::render('<x-ui::button href="/billing">Billing</x-ui::button>');

    $link = $dom->querySelector('a');

    expect($link->getAttribute('href'))->toBe('/billing')
        ->and($dom->querySelector('button'))->toBeNull()
        ->and($link->textContent)->toContain('Billing');
});

it('renders a button as a type=button button by default', function (): void {
    $button = Html::render('<x-ui::button>Go</x-ui::button>')->querySelector('button');

    expect($button->getAttribute('type'))->toBe('button');
});

it('lets a button submit', function (): void {
    $button = Html::render('<x-ui::button type="submit">Go</x-ui::button>')->querySelector('button');

    expect($button->getAttribute('type'))->toBe('submit');
});

it('disables a button element', function (): void {
    $button = Html::render('<x-ui::button disabled>Go</x-ui::button>')->querySelector('button');

    expect($button->hasAttribute('disabled'))->toBeTrue();
});

it('marks a disabled link aria-disabled and drops its href', function (): void {
    $link = Html::render('<x-ui::button href="/billing" disabled>Billing</x-ui::button>')->querySelector('a');

    expect($link->getAttribute('aria-disabled'))->toBe('true')
        ->and($link->hasAttribute('href'))->toBeFalse()
        ->and($link->getAttribute('tabindex'))->toBe('-1');
});

it('gives an icon-only button its accessible name from the label', function (): void {
    $button = Html::render('<x-ui::button icon="fas-check" label="Approve" />')->querySelector('button');

    expect($button->getAttribute('aria-label'))->toBe('Approve')
        ->and($button->querySelector('svg')->getAttribute('aria-hidden'))->toBe('true');
});

it('does not override visible button text with the label', function (): void {
    $button = Html::render('<x-ui::button label="Hidden">Save</x-ui::button>')->querySelector('button');

    expect($button->hasAttribute('aria-label'))->toBeFalse();
});

it('refuses an icon-only button without a label', function (): void {
    Html::render('<x-ui::button icon="fas-check" />');
})->throws(ViewException::class, 'needs a label');

it('shows a busy spinner for the livewire action it triggers', function (): void {
    $button = Html::render('<x-ui::button wire:click="save">Save</x-ui::button>')->querySelector('button');

    expect($button->getAttribute('wire:target'))->toBe('save')
        ->and($button->getAttribute('wire:loading.attr'))->toBe('aria-busy')
        ->and($button->querySelector('svg')->getAttribute('wire:target'))->toBe('save');
});

it('keeps the primary fill readable on both themes', function (): void {
    $class = Html::render('<x-ui::button>Go</x-ui::button>')->querySelector('button')->getAttribute('class');

    expect($class)->toContain('bg-primary-700')->toContain('text-white')
        ->toContain('dark:bg-primary-400')->toContain('dark:text-gray-950')
        ->toContain('dark:hover:bg-primary-300');
});

it('keeps the danger fill readable on both themes', function (): void {
    $class = Html::render('<x-ui::button variant="danger">Go</x-ui::button>')->querySelector('button')->getAttribute('class');

    expect($class)->toContain('bg-rose-700')->toContain('dark:bg-rose-400')->toContain('dark:text-gray-950');
});

it('renders a badge with an icon', function (): void {
    $badge = Html::render('<x-ui::badge variant="success" icon="fas-check">Active</x-ui::badge>')->querySelector('span');

    expect($badge->textContent)->toContain('Active')
        ->and($badge->querySelector('svg'))->not->toBeNull()
        ->and($badge->getAttribute('class'))->toContain('text-emerald-700');
});

it('renders an icon given as a named slot as it is', function (): void {
    $badge = Html::render('<x-ui::badge><x-slot:icon><b id="mine">!</b></x-slot:icon>
Hot</x-ui::badge>')->querySelector('span');

    expect($badge->querySelector('#mine'))->not->toBeNull()
        ->and($badge->querySelector('svg'))->toBeNull();
});

it('labels a card section by its heading', function (): void {
    $dom = Html::render('<x-ui::card heading="Billing" :level="3">Body</x-ui::card>');

    $section = $dom->querySelector('section');
    $heading = $dom->getElementById($section->getAttribute('aria-labelledby'));

    expect($heading->localName)->toBe('h3')
        ->and($heading->textContent)->toBe('Billing');
});

it('takes a card heading from a named slot and renders a footer', function (): void {
    $dom = Html::render('<x-ui::card><x-slot:heading>Usage</x-slot:heading>
Body
<x-slot:footer>Footer text</x-slot:footer></x-ui::card>');

    $section = $dom->querySelector('section');

    expect($dom->getElementById($section->getAttribute('aria-labelledby'))->localName)->toBe('h2')
        ->and($section->textContent)->toContain('Footer text');
});

it('leaves a card without a heading unlabelled', function (): void {
    $section = Html::render('<x-ui::card>Body</x-ui::card>')->querySelector('section');

    expect($section->hasAttribute('aria-labelledby'))->toBeFalse();
});

it('uses an alert role that matches its urgency', function (string $variant, string $role): void {
    $alert = Html::render("<x-ui::alert variant=\"{$variant}\">Message</x-ui::alert>")->querySelector('[role]');

    expect($alert->getAttribute('role'))->toBe($role);
})->with([
    ['danger', 'alert'],
    ['warning', 'alert'],
    ['info', 'status'],
    ['success', 'status'],
]);

it('renders an alert heading and a labelled dismiss button when dismissible', function (): void {
    $dom = Html::render('<x-ui::alert heading="Careful" dismissible>Message</x-ui::alert>');

    $alert = $dom->querySelector('[role]');

    expect($alert->textContent)->toContain('Careful')
        ->and($alert->hasAttribute('x-data'))->toBeTrue()
        ->and($alert->querySelector('button')->getAttribute('aria-label'))->toBe('Dismiss');
});

it('renders no dismiss button on a plain alert', function (): void {
    $alert = Html::render('<x-ui::alert>Message</x-ui::alert>')->querySelector('[role]');

    expect($alert->querySelector('button'))->toBeNull()
        ->and($alert->hasAttribute('x-data'))->toBeFalse();
});

it('labels the modal dialog by its heading and describes it', function (): void {
    $dom = Html::render('<x-ui::modal name="edit" heading="Edit account" description="Change the name.">Body<x-slot:footer>Footer</x-slot:footer></x-ui::modal>');

    $dialog = $dom->querySelector('[role=dialog]');

    expect($dialog->getAttribute('aria-modal'))->toBe('true')
        ->and($dom->getElementById($dialog->getAttribute('aria-labelledby'))->textContent)->toBe('Edit account')
        ->and($dom->getElementById($dialog->getAttribute('aria-describedby'))->textContent)->toBe('Change the name.')
        ->and($dialog->textContent)->toContain('Footer');
});

it('describes the modal only when it has a description', function (): void {
    $dialog = Html::render('<x-ui::modal name="edit" heading="Edit">Body</x-ui::modal>')->querySelector('[role=dialog]');

    expect($dialog->hasAttribute('aria-describedby'))->toBeFalse();
});

it('gives the modal a labelled close button and passes wire:model to its root', function (): void {
    $dom = Html::render('<x-ui::modal name="edit" heading="Edit" wire:model="showEdit">Body</x-ui::modal>');

    expect($dom->querySelector('[role=dialog] button')->getAttribute('aria-label'))->toBe('Close')
        ->and($dom->querySelector('[x-modelable]')->getAttribute('wire:model'))->toBe('showEdit');
});

it('renders the toasts live region in the page from the start', function (): void {
    $region = Html::render('<x-ui::toasts />')->querySelector('[aria-live]');

    expect($region->getAttribute('aria-live'))->toBe('polite')
        ->and($region->getAttribute('role'))->toBe('status')
        ->and($region->getAttribute('x-on:ui-toast.window'))->not->toBeNull();
});

it('hands toasts flashed to the session to the live region', function (): void {
    session()->flash('ui.toasts', [['message' => 'Saved earlier', 'variant' => 'info']]);

    $region = Html::render('<x-ui::toasts />')->querySelector('[aria-live]');

    expect($region->getAttribute('x-data'))->toContain('Saved earlier');
});
