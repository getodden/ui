<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Odden\Ui\Tests\Support\Html;

it('renders date, datetime and number as typed inputs with the field behaviour', function (string $component, string $type): void {
    view()->share('errors', Html::errors(['form.when' => 'Pick a later one.']));
    $dom = Html::render("<{$component} label=\"When\" wire:model=\"form.when\" hint=\"Local time\" required />");

    $input = $dom->querySelector('input');

    expect($input->getAttribute('type'))->toBe($type)
        ->and($input->getAttribute('aria-invalid'))->toBe('true')
        ->and($input->getAttribute('aria-describedby'))->toBe('ui-form-when-hint ui-form-when-error')
        ->and($input->hasAttribute('required'))->toBeTrue()
        ->and($dom->querySelector('label')->getAttribute('for'))->toBe('ui-form-when')
        ->and($dom->getElementById('ui-form-when-error')->textContent)->toBe('Pick a later one.');
})->with([
    'date' => ['x-ui::date', 'date'],
    'datetime' => ['x-ui::datetime', 'datetime-local'],
    'number' => ['x-ui::number', 'number'],
]);

it('passes min, max and step through to the number input', function (): void {
    $input = Html::render('<x-ui::number label="Seats" name="seats" min="1" max="9" step="2" />')->querySelector('input');

    expect($input->getAttribute('min'))->toBe('1')
        ->and($input->getAttribute('max'))->toBe('9')
        ->and($input->getAttribute('step'))->toBe('2');
});

it('shows a hidden symbol and a currency code the input is described by', function (): void {
    $dom = Html::render('<x-ui::currency label="Amount" name="amount" symbol="€" currency="EUR" hint="Gross" />');

    $input = $dom->querySelector('input');
    $ids = explode(' ', $input->getAttribute('aria-describedby'));

    expect($input->getAttribute('type'))->toBe('number')
        ->and($input->getAttribute('step'))->toBe('0.01')
        ->and($input->getAttribute('inputmode'))->toBe('decimal')
        ->and($ids[0])->toBe('ui-amount-hint')
        ->and($dom->getElementById($ids[1])->textContent)->toBe('EUR')
        ->and($dom->querySelector('[aria-hidden=true]')->textContent)->toBe('€');
});

it('keeps a given step on the currency input', function (): void {
    $input = Html::render('<x-ui::currency label="Amount" name="amount" step="1" />')->querySelector('input');

    expect($input->getAttribute('step'))->toBe('1')
        ->and($input->hasAttribute('aria-describedby'))->toBeFalse();
});

it('renders a checkbox next to its label with the error attached', function (): void {
    view()->share('errors', Html::errors(['agreed' => 'You must agree.']));
    $dom = Html::render('<x-ui::checkbox label="I agree" wire:model="agreed" />');

    $input = $dom->querySelector('input');

    expect($input->getAttribute('type'))->toBe('checkbox')
        ->and($input->getAttribute('aria-invalid'))->toBe('true')
        ->and($input->getAttribute('aria-describedby'))->toBe('ui-agreed-error')
        ->and($dom->querySelector('label')->getAttribute('for'))->toBe('ui-agreed');
});

it('renders a radio group as a labelled fieldset with one shared name', function (): void {
    view()->share('errors', Html::errors(['method' => 'Choose one.']));
    $dom = Html::render('<x-ui::radio-group label="Method" wire:model.live="method" hint="How" :options="[\'email\' => \'Email\', \'phone\' => [\'label\' => \'Phone\', \'hint\' => \'Weekdays\']]" value="phone" />');

    $fieldset = $dom->querySelector('fieldset');
    $radios = $dom->querySelectorAll('input[type=radio]');

    expect($dom->querySelector('legend')->textContent)->toBe('Method')
        ->and($fieldset->getAttribute('aria-describedby'))->toBe('ui-method-hint ui-method-error')
        ->and($radios)->toHaveCount(2)
        ->and($radios[0]->getAttribute('name'))->toBe('method')
        ->and($radios[1]->getAttribute('name'))->toBe('method')
        ->and($radios[0]->hasAttribute('wire:model.live'))->toBeTrue()
        ->and($radios[1]->hasAttribute('wire:model.live'))->toBeTrue()
        ->and($radios[0]->hasAttribute('checked'))->toBeFalse()
        ->and($radios[1]->hasAttribute('checked'))->toBeTrue()
        ->and($radios[0]->getAttribute('aria-invalid'))->toBe('true')
        ->and($dom->querySelector('label[for=ui-method-1]')->textContent)->toBe('Phone')
        ->and($radios[1]->getAttribute('aria-describedby'))->toBe('ui-method-1-hint')
        ->and($dom->getElementById('ui-method-1-hint')->textContent)->toBe('Weekdays')
        ->and($dom->getElementById('ui-method-error')->textContent)->toBe('Choose one.');
});

it('leaves the group itself without wire:model so the radios carry it', function (): void {
    $fieldset = Html::render('<x-ui::radio-group label="Method" wire:model="method" :options="[\'a\' => \'A\']" />')->querySelector('fieldset');

    expect($fieldset->hasAttribute('wire:model'))->toBeFalse();
});

it('refuses new fields without a label', function (string $component): void {
    Html::render("<{$component} name=\"name\" />");
})->throws(ViewException::class, 'needs a label')->with([
    'x-ui::date', 'x-ui::datetime', 'x-ui::number', 'x-ui::currency', 'x-ui::checkbox', 'x-ui::radio-group', 'x-ui::multiselect', 'x-ui::file',
]);

it('renders a file input with its error', function (): void {
    view()->share('errors', Html::errors(['photo' => 'Too big.']));
    $dom = Html::render('<x-ui::file label="Photo" wire:model="photo" accept="image/*" />');

    $input = $dom->querySelector('input');

    expect($input->getAttribute('type'))->toBe('file')
        ->and($input->getAttribute('accept'))->toBe('image/*')
        ->and($input->getAttribute('aria-invalid'))->toBe('true')
        ->and($dom->getElementById('ui-photo-error')->textContent)->toBe('Too big.');
});

it('finds the error of a list item under the list key', function (): void {
    view()->share('errors', Html::errors(['photos.1' => 'Not an image.']));
    $dom = Html::render('<x-ui::file label="Photos" wire:model="photos" multiple />');

    expect($dom->getElementById('ui-photos-error')->textContent)->toBe('Not an image.');
});

it('prefers the error of the key itself over its items', function (): void {
    view()->share('errors', Html::errors(['tags' => 'Pick two.', 'tags.0' => 'Bad tag.']));
    $dom = Html::render('<x-ui::multiselect label="Tags" wire:model="tags" :options="[\'a\' => \'A\']" />');

    expect($dom->getElementById('ui-tags-error')->textContent)->toBe('Pick two.');
});

it('renders a multiselect as a labelled combobox over a multiselectable listbox', function (): void {
    view()->share('errors', Html::errors(['tags' => 'Pick two.']));
    $dom = Html::render('<x-ui::multiselect label="Tags" wire:model="tags" hint="Any" :options="[\'a\' => \'Alpha\', \'b\' => \'Beta\']" :value="[\'b\']" />');

    $trigger = $dom->querySelector('button[role=combobox]');
    $listbox = $dom->querySelector('[role=listbox]');
    $options = $dom->querySelectorAll('[role=option]');

    expect($trigger->getAttribute('aria-haspopup'))->toBe('listbox')
        ->and($trigger->getAttribute('aria-controls'))->toBe($listbox->getAttribute('id'))
        ->and($trigger->getAttribute('aria-labelledby'))->toBe('ui-tags-label ui-tags-summary')
        ->and($trigger->getAttribute('aria-invalid'))->toBe('true')
        ->and($trigger->hasAttribute('wire:model'))->toBeFalse()
        ->and($trigger->hasAttribute('required'))->toBeFalse()
        ->and($listbox->getAttribute('aria-multiselectable'))->toBe('true')
        ->and($listbox->getAttribute('aria-labelledby'))->toBe('ui-tags-label')
        ->and($options)->toHaveCount(2)
        ->and($options[1]->getAttribute('data-value'))->toBe('b')
        ->and($dom->getElementById('ui-tags-label')->textContent)->toBe('Tags')
        ->and($dom->querySelector('[x-modelable]')->hasAttribute('wire:model'))->toBeTrue();
});

it('seeds the multiselect with the given values as strings', function (): void {
    $initial = Html::render('<x-ui::multiselect label="Tags" name="tags" :options="[1 => \'One\', 2 => \'Two\']" :value="[2]" />')->querySelector('[x-data]')->getAttribute('data-initial');

    expect($initial)->toBe('["2"]');
});

it('posts the multiselect values under its name only when a name is given', function (): void {
    $named = Html::render('<x-ui::multiselect label="Tags" name="tags" :options="[\'a\' => \'A\']" />');
    $bound = Html::render('<x-ui::multiselect label="Tags" wire:model="tags" :options="[\'a\' => \'A\']" />');

    expect($named->querySelector('template[x-for]')->innerHTML)->toContain('name="tags[]"')
        ->and($bound->querySelector('template[x-for]'))->toBeNull();
});

it('renders a combobox with the ARIA 1.2 pattern and the search method', function (): void {
    view()->share('errors', Html::errors(['customer' => 'Pick a customer.']));
    $dom = Html::render('<x-ui::combobox label="Customer" wire:model="customer" search="searchCustomers" value-label="Ada" hint="Type" />');

    $input = $dom->querySelector('input[role=combobox]');

    expect($input->getAttribute('aria-autocomplete'))->toBe('list')
        ->and($input->getAttribute('aria-controls'))->toBe('ui-customer-listbox')
        ->and($input->getAttribute('aria-invalid'))->toBe('true')
        ->and($input->getAttribute('aria-describedby'))->toBe('ui-customer-hint ui-customer-error')
        ->and($input->getAttribute('autocomplete'))->toBe('off')
        ->and($input->hasAttribute('name'))->toBeFalse()
        ->and($input->hasAttribute('wire:model'))->toBeFalse()
        ->and($dom->querySelector('[role=listbox]')->getAttribute('aria-labelledby'))->toBe('ui-customer-label')
        ->and($dom->querySelector('[role=status]'))->not->toBeNull()
        ->and($dom->querySelector('[x-modelable]')->getAttribute('x-data'))->toContain('searchCustomers');
});

it('refuses a combobox search that is not a method name', function (?string $value): void {
    Html::render('<x-ui::combobox label="Customer" name="customer" :search="$value" />', ['value' => $value]);
})->throws(ViewException::class, 'name of a public method')->with([
    'a call' => 'search()', 'a path' => 'a.b', 'spaces' => 'a b', 'missing' => null,
]);

it('posts the combobox value only when a name is given', function (): void {
    $named = Html::render('<x-ui::combobox label="Customer" name="customer" search="find" />');
    $bound = Html::render('<x-ui::combobox label="Customer" wire:model="customer" search="find" />');

    expect($named->querySelector('input[type=hidden][name=customer]'))->not->toBeNull()
        ->and($bound->querySelector('input[type=hidden]'))->toBeNull();
});

it('renders a collapsible section as a heading button that controls its region', function (): void {
    $dom = Html::render('<x-ui::collapsible heading="Advanced" description="Rare" level="3">Body</x-ui::collapsible>');

    $button = $dom->querySelector('h3 button');
    $region = $dom->querySelector('[role=region]');

    expect($button->getAttribute('aria-expanded'))->toBe('false')
        ->and($button->getAttribute('aria-controls'))->toBe($region->getAttribute('id'))
        ->and($region->getAttribute('aria-labelledby'))->toBe($button->getAttribute('id'))
        ->and($region->getAttribute('style'))->toContain('display: none')
        ->and($button->textContent)->toContain('Rare');
});

it('opens a collapsible section from the server', function (): void {
    $dom = Html::render('<x-ui::collapsible heading="Advanced" open>Body</x-ui::collapsible>');

    expect($dom->querySelector('button')->getAttribute('aria-expanded'))->toBe('true')
        ->and($dom->querySelector('[role=region]')->hasAttribute('style'))->toBeFalse();
});

it('refuses a collapsible, empty state, page header and dropdown item without their required prop', function (string $template): void {
    Html::render($template);
})->throws(ViewException::class, 'needs a')->with([
    'collapsible' => '<x-ui::collapsible>Body</x-ui::collapsible>',
    'empty state' => '<x-ui::empty-state />',
    'page header' => '<x-ui::page-header />',
    'dropdown' => '<x-ui::dropdown />',
    'tabs without id' => '<x-ui::tabs label="Sections" :tabs="[\'a\' => \'A\']" />',
    'tabs without label' => '<x-ui::tabs id="t" :tabs="[\'a\' => \'A\']" />',
    'tab' => '<x-ui::tabs id="t" label="x" :tabs="[]"><x-ui::tab>x</x-ui::tab></x-ui::tabs>',
    'description item' => '<x-ui::description-list.item>x</x-ui::description-list.item>',
]);

it('links tabs and panels both ways and selects the first tab by default', function (): void {
    $dom = Html::render('<x-ui::tabs id="record" label="Sections" :tabs="[\'details\' => \'Details\', \'notes\' => \'Notes\']"><x-ui::tab name="details">D</x-ui::tab><x-ui::tab name="notes">N</x-ui::tab></x-ui::tabs>');

    $tabs = $dom->querySelectorAll('[role=tab]');
    $panels = $dom->querySelectorAll('[role=tabpanel]');

    expect($dom->querySelector('[role=tablist]')->getAttribute('aria-label'))->toBe('Sections')
        ->and($tabs)->toHaveCount(2)
        ->and($panels)->toHaveCount(2)
        ->and($tabs[0]->getAttribute('aria-selected'))->toBe('true')
        ->and($tabs[0]->getAttribute('tabindex'))->toBe('0')
        ->and($tabs[1]->getAttribute('aria-selected'))->toBe('false')
        ->and($tabs[1]->getAttribute('tabindex'))->toBe('-1');

    foreach ([0, 1] as $index) {
        expect($tabs[$index]->getAttribute('aria-controls'))->toBe($panels[$index]->getAttribute('id'))
            ->and($panels[$index]->getAttribute('aria-labelledby'))->toBe($tabs[$index]->getAttribute('id'));
    }
});

it('falls back to the first tab when the selected one does not exist', function (): void {
    $tabs = Html::render('<x-ui::tabs id="record" label="Sections" selected="gone" :tabs="[\'a\' => \'A\', \'b\' => \'B\']" />')->querySelectorAll('[role=tab]');

    expect($tabs[0]->getAttribute('aria-selected'))->toBe('true');
});

it('selects the requested tab', function (): void {
    $tabs = Html::render('<x-ui::tabs id="record" label="Sections" selected="b" :tabs="[\'a\' => \'A\', \'b\' => \'B\']" />')->querySelectorAll('[role=tab]');

    expect($tabs[1]->getAttribute('aria-selected'))->toBe('true')
        ->and($tabs[1]->getAttribute('tabindex'))->toBe('0');
});

it('keeps wire:model on the tabs root and aria attributes on the tablist', function (): void {
    $dom = Html::render('<x-ui::tabs id="record" aria-labelledby="heading" wire:model="tab" :tabs="[\'a\' => \'A\']" />');

    expect($dom->getElementById('record')->hasAttribute('wire:model'))->toBeTrue()
        ->and($dom->querySelector('[role=tablist]')->getAttribute('aria-labelledby'))->toBe('heading')
        ->and($dom->getElementById('record')->hasAttribute('aria-labelledby'))->toBeFalse();
});

it('renders a dropdown as a menu button with menu items', function (): void {
    $dom = Html::render('<x-ui::dropdown id="actions" label="Actions" align="end"><x-ui::dropdown.item wire:click="archive">Archive</x-ui::dropdown.item><x-ui::dropdown.item href="/export">Export</x-ui::dropdown.item><x-ui::dropdown.item disabled>Locked</x-ui::dropdown.item><x-ui::dropdown.item variant="danger">Delete</x-ui::dropdown.item></x-ui::dropdown>');

    $trigger = $dom->getElementById('actions-trigger');
    $menu = $dom->querySelector('[role=menu]');
    $items = $dom->querySelectorAll('[role=menuitem]');

    expect($trigger->getAttribute('aria-haspopup'))->toBe('menu')
        ->and($trigger->getAttribute('aria-controls'))->toBe('actions-menu')
        ->and($menu->getAttribute('aria-labelledby'))->toBe('actions-trigger')
        ->and($menu->getAttribute('class'))->toContain('right-0')
        ->and($items)->toHaveCount(4)
        ->and($items[0]->nodeName)->toBe('BUTTON')
        ->and($items[0]->getAttribute('tabindex'))->toBe('-1')
        ->and($items[0]->hasAttribute('wire:click'))->toBeTrue()
        ->and($items[1]->nodeName)->toBe('A')
        ->and($items[1]->getAttribute('href'))->toBe('/export')
        ->and($items[2]->hasAttribute('disabled'))->toBeTrue()
        ->and($items[3]->getAttribute('class'))->toContain('text-rose-700');
});

it('renders an icon-only dropdown with an accessible name', function (): void {
    $trigger = Html::render('<x-ui::dropdown id="more" label="More actions" icon-only><x-ui::dropdown.item>Edit</x-ui::dropdown.item></x-ui::dropdown>')->getElementById('more-trigger');

    expect($trigger->getAttribute('aria-label'))->toBe('More actions')
        ->and(trim($trigger->textContent))->toBe('');
});

it('renders breadcrumbs with the last one as the current page', function (): void {
    $dom = Html::render('<x-ui::page-header title="Contact" description="One person" :breadcrumbs="[[\'label\' => \'Home\', \'href\' => \'/\'], [\'label\' => \'Contacts\', \'href\' => \'/contacts\'], [\'label\' => \'Ada\']]"><x-slot:actions><button>Edit</button></x-slot:actions></x-ui::page-header>');

    $links = $dom->querySelectorAll('nav a');

    expect($dom->querySelector('h1')->textContent)->toBe('Contact')
        ->and($dom->querySelector('nav')->getAttribute('aria-label'))->toBe('Breadcrumb')
        ->and($links)->toHaveCount(2)
        ->and($links[1]->getAttribute('href'))->toBe('/contacts')
        ->and($dom->querySelector('[aria-current=page]')->textContent)->toBe('Ada')
        ->and($dom->querySelectorAll('nav li'))->toHaveCount(3)
        ->and($dom->querySelector('button')->textContent)->toBe('Edit');
});

it('renders a page header without breadcrumbs or actions', function (): void {
    $dom = Html::render('<x-ui::page-header title="Contacts" level="2" />');

    expect($dom->querySelector('h2')->textContent)->toBe('Contacts')
        ->and($dom->querySelector('nav'))->toBeNull();
});

it('does not link a breadcrumb that is not the current page and has no href', function (): void {
    $dom = Html::render('<x-ui::page-header title="Contact" :breadcrumbs="[[\'label\' => \'Section\'], [\'label\' => \'Ada\']]" />');

    expect($dom->querySelector('nav a'))->toBeNull()
        ->and($dom->querySelectorAll('nav li'))->toHaveCount(2);
});

it('renders a description list from items and slot items, marking empty values', function (): void {
    $dom = Html::render('<x-ui::description-list :items="[\'Name\' => \'Acme\', \'Phone\' => \'\']"><x-ui::description-list.item label="Owner">Ada</x-ui::description-list.item></x-ui::description-list>');

    $terms = $dom->querySelectorAll('dt');
    $details = $dom->querySelectorAll('dd');

    expect($terms)->toHaveCount(3)
        ->and($terms[0]->textContent)->toBe('Name')
        ->and(trim($details[0]->textContent))->toBe('Acme')
        ->and($details[1]->textContent)->toContain('Not set')
        ->and(trim($details[2]->textContent))->toBe('Ada');
});

it('escapes description list values', function (): void {
    $dom = Html::render('<x-ui::description-list :items="[\'Note\' => \'<b>x</b>\']" />');

    expect($dom->querySelector('dd b'))->toBeNull();
});

it('renders an empty state with heading, description and actions', function (): void {
    $dom = Html::render('<x-ui::empty-state heading="No contacts" description="Add one." icon="fas-inbox"><x-slot:actions><a href="/new">Add</a></x-slot:actions></x-ui::empty-state>');

    expect($dom->querySelector('h3')->textContent)->toBe('No contacts')
        ->and($dom->querySelector('p')->textContent)->toBe('Add one.')
        ->and($dom->querySelector('svg')->getAttribute('aria-hidden'))->toBe('true')
        ->and($dom->querySelector('a')->getAttribute('href'))->toBe('/new');
});

it('exposes a required multiselect as required to assistive technology', function (): void {
    $required = Html::render('<x-ui::multiselect label="Tags" name="tags" :options="[\'a\' => \'A\']" required />')->querySelector('button[role=combobox]');
    $optional = Html::render('<x-ui::multiselect label="Tags" name="tags" :options="[\'a\' => \'A\']" />')->querySelector('button[role=combobox]');

    expect($required->getAttribute('aria-required'))->toBe('true')
        ->and($required->hasAttribute('required'))->toBeFalse()
        ->and($optional->hasAttribute('aria-required'))->toBeFalse();
});

it('makes room for a currency symbol of any length', function (string $symbol, string $padding): void {
    $input = Html::render('<x-ui::currency label="Amount" name="amount" symbol="'.$symbol.'" />')->querySelector('input');

    expect($input->getAttribute('style'))->toBe('padding-left: calc(1.25rem + '.$padding.'ch);');
})->with([
    'one character' => ['$', '1'],
    'two characters' => ['kr', '2'],
    'three characters' => ['CHF', '3'],
]);

it('shows a visible focus indicator on a focused menu item', function (): void {
    $item = Html::render('<x-ui::dropdown.item>Export</x-ui::dropdown.item>')->querySelector('[role=menuitem]');

    expect($item->getAttribute('class'))->toContain('focus-visible:outline-primary-700')
        ->and($item->getAttribute('class'))->not->toContain('focus-visible:outline-none');
});

it('keeps server values out of the x-data of the combobox, multiselect and tabs', function (): void {
    $combobox = Html::render('<x-ui::combobox label="Customer" wire:model="customer" search="find" value="7" value-label="Ada" />')->querySelector('[x-modelable]');
    $multiselect = Html::render('<x-ui::multiselect label="Tags" wire:model="tags" :options="[\'a\' => \'Alpha\']" :value="[\'a\']" />')->querySelector('[x-modelable]');
    $tabs = Html::render('<x-ui::tabs id="t" label="Sections" wire:model="tab" selected="b" :tabs="[\'a\' => \'A\', \'b\' => \'B\']" />')->querySelector('[x-modelable]');

    expect($combobox->getAttribute('x-data'))->not->toContain('Ada')->not->toContain('"7"')
        ->and($combobox->getAttribute('data-value'))->toBe('7')
        ->and($combobox->getAttribute('data-label'))->toBe('Ada')
        ->and($multiselect->getAttribute('x-data'))->not->toContain('Alpha')
        ->and($multiselect->getAttribute('data-initial'))->toBe('["a"]')
        ->and($tabs->getAttribute('x-data'))->not->toContain('"b"')
        ->and($tabs->getAttribute('data-initial'))->toBe('b');
});
