<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Livewire\Livewire;
use Odden\Ui\Enums\RepeaterDirection;
use Odden\Ui\Support\RepeaterRows;
use Odden\Ui\Support\RepeaterSpec;
use Odden\Ui\Tests\Support\Html;
use Odden\Ui\Tests\Support\RepeaterComponent;
use Odden\Ui\Tests\Support\UnregisteredRepeaterComponent;

function repeaterNames(mixed $items): array
{
    return array_column($items, 'name');
}

function keyOf(mixed $component, int $index): string
{
    return $component->get('items')[$index]['_key'];
}

it('gives every row a unique key', function (): void {
    $rows = RepeaterRows::withKeys([['name' => 'a'], ['name' => 'b', '_key' => 'same'], ['name' => 'c', '_key' => 'same'], ['name' => 'd', '_key' => '']]);

    $keys = array_column($rows, '_key');

    expect($rows)->toHaveCount(4)
        ->and($keys[1])->toBe('same')
        ->and(array_unique($keys))->toHaveCount(4)
        ->and(array_search('', $keys, true))->toBeFalse();
});

it('refuses rows that are not arrays when keying', function (): void {
    RepeaterRows::withKeys(['a@x.com', 'b@x.com']);
})->throws(InvalidArgumentException::class);

it('strips the keys before saving', function (): void {
    $rows = RepeaterRows::withKeys([['name' => 'a'], ['name' => 'b']]);

    expect(RepeaterRows::withoutKeys($rows))->toBe([['name' => 'a'], ['name' => 'b']]);
});

it('keeps existing keys across calls', function (): void {
    $once = RepeaterRows::withKeys([['name' => 'a'], ['name' => 'b']]);

    expect(RepeaterRows::withKeys($once))->toBe($once);
});

it('adds a blank keyed row until the maximum', function (): void {
    $spec = new RepeaterSpec(max: 2, blank: ['name' => '']);

    $one = RepeaterRows::add([], $spec);
    $two = RepeaterRows::add($one, $spec);
    $still = RepeaterRows::add($two, $spec);

    expect($one[0]['name'])->toBe('')
        ->and($one[0]['_key'])->not->toBe('')
        ->and($two)->toHaveCount(2)
        ->and($still)->toBe($two);
});

it('removes a row by key but never below the minimum', function (): void {
    $rows = RepeaterRows::withKeys([['name' => 'a'], ['name' => 'b']]);
    $spec = new RepeaterSpec(min: 1);

    $one = RepeaterRows::remove($rows, $spec, $rows[0]['_key']);
    $atMinimum = RepeaterRows::remove($one, $spec, $one[0]['_key']);

    expect(repeaterNames($one))->toBe(['b'])
        ->and($atMinimum)->toBe($one)
        ->and(RepeaterRows::remove($rows, $spec, 'missing'))->toBe($rows);
});

it('moves a row one step and stops at the ends', function (): void {
    $rows = RepeaterRows::withKeys([['name' => 'a'], ['name' => 'b'], ['name' => 'c']]);

    $down = RepeaterRows::move($rows, $rows[0]['_key'], RepeaterDirection::Down);
    $up = RepeaterRows::move($rows, $rows[2]['_key'], RepeaterDirection::Up);

    expect(repeaterNames($down))->toBe(['b', 'a', 'c'])
        ->and(repeaterNames($up))->toBe(['a', 'c', 'b'])
        ->and(RepeaterRows::move($rows, $rows[0]['_key'], RepeaterDirection::Up))->toBe($rows)
        ->and(RepeaterRows::move($rows, $rows[2]['_key'], RepeaterDirection::Down))->toBe($rows)
        ->and(RepeaterRows::move($rows, 'missing', RepeaterDirection::Up))->toBe($rows);
});

it('reorders only to a full permutation of the current keys', function (): void {
    $rows = RepeaterRows::withKeys([['name' => 'a'], ['name' => 'b'], ['name' => 'c']]);
    [$a, $b, $c] = array_column($rows, '_key');

    expect(repeaterNames(RepeaterRows::reorder($rows, [$c, $a, $b])))->toBe(['c', 'a', 'b'])
        ->and(RepeaterRows::reorder($rows, [$a, $b]))->toBe($rows)
        ->and(RepeaterRows::reorder($rows, [$a, $a, $b]))->toBe($rows)
        ->and(RepeaterRows::reorder($rows, [$a, $b, 'other']))->toBe($rows)
        ->and(RepeaterRows::reorder($rows, ['x' => $a, 'y' => $b, 'z' => $c]))->toBe($rows)
        ->and(RepeaterRows::reorder($rows, [['x'], $a, $b]))->toBe($rows);
});

it('keys the rows when the component first renders', function (): void {
    $component = Livewire::test(RepeaterComponent::class);

    expect(array_filter(array_column($component->get('items'), '_key')))->toHaveCount(3);
});

it('keys rows that arrive keyless from the browser', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->set('items', [['name' => 'x'], ['name' => 'y']]);

    expect(array_filter(array_column($component->get('items'), '_key')))->toHaveCount(2);
});

it('adds, removes and moves rows through the trait', function (): void {
    $component = Livewire::test(RepeaterComponent::class);
    $first = keyOf($component, 0);

    $component->call('moveRepeaterRow', 'items', $first, 'down');
    expect(repeaterNames($component->get('items')))->toBe(['Second', 'First', 'Third']);

    $component->call('removeRepeaterRow', 'items', $first);
    expect(repeaterNames($component->get('items')))->toBe(['Second', 'Third']);

    $component->call('addRepeaterRow', 'items');
    expect(repeaterNames($component->get('items')))->toBe(['Second', 'Third', '']);

    $keys = array_reverse(array_column($component->get('items'), '_key'));
    $component->call('reorderRepeaterRows', 'items', $keys);
    expect(repeaterNames($component->get('items')))->toBe(['', 'Third', 'Second']);
});

it('enforces the minimum and maximum on the server', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->call('addRepeaterRow', 'items');

    $component->call('addRepeaterRow', 'items');
    expect($component->get('items'))->toHaveCount(4);

    foreach (range(1, 5) as $ignored) {
        $component->call('removeRepeaterRow', 'items', keyOf($component, 0));
    }

    expect($component->get('items'))->toHaveCount(1);
});

it('ignores properties that are not registered repeaters and unknown directions', function (): void {
    $component = Livewire::test(RepeaterComponent::class);
    $before = $component->get('items');

    $component->call('addRepeaterRow', 'untouched')
        ->call('removeRepeaterRow', 'untouched', 'x')
        ->call('moveRepeaterRow', 'untouched', 'x', 'up')
        ->call('reorderRepeaterRows', 'untouched', [])
        ->call('addRepeaterRow', 'nothing')
        ->call('moveRepeaterRow', 'items', keyOf($component, 0), 'sideways')
        ->assertSet('untouched', 'keep');

    expect($component->get('items'))->toBe($before);
});

it('shows each row error beside its own field and keeps keys after a reorder', function (): void {
    $component = Livewire::test(RepeaterComponent::class);
    $component->set('items.1.name', 'x')->call('save');

    $component->assertSee('The name field must be at least 2 characters.');
    $html = $component->html();

    expect(substr_count($html, 'must be at least 2 characters'))->toBe(1);

    $dom = Html::render($html);
    $rows = $dom->querySelectorAll('li[data-repeater-key]');

    expect($rows)->toHaveCount(3)
        ->and($rows[1]->querySelector('[role=alert], p[id$="-error"]')?->textContent)->toContain('at least 2 characters')
        ->and($rows[0]->querySelector('p[id$="-error"]'))->toBeNull();
});

it('renders an accessible list of labelled rows', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->set('items', [['name' => 'a'], ['name' => 'b']]);
    $rows = $component->get('items');
    $dom = Html::render($component->html());

    $group = $dom->querySelector('[x-data]');
    $items = $dom->querySelectorAll('ol > li');
    $first = $items[0]->querySelector('[data-repeater-row]');

    expect($group->getAttribute('role'))->toBe('group')
        ->and($group->getAttribute('aria-labelledby'))->toBe('ui-items-label')
        ->and($dom->getElementById('ui-items-label')->textContent)->toBe('People')
        ->and($items)->toHaveCount(2)
        ->and($items[0]->getAttribute('data-repeater-key'))->toBe($rows[0]['_key'])
        ->and($first->getAttribute('aria-labelledby'))->toBe('ui-items-row-0-title')
        ->and($dom->getElementById('ui-items-row-1-title')->textContent)->toBe('Person 2')
        ->and($items[0]->querySelector('[data-repeater-action=up]')->hasAttribute('disabled'))->toBeTrue()
        ->and($items[0]->querySelector('[data-repeater-action=down]')->hasAttribute('disabled'))->toBeFalse()
        ->and($items[1]->querySelector('[data-repeater-action=down]')->hasAttribute('disabled'))->toBeTrue()
        ->and($items[0]->querySelector('[data-repeater-action=remove]')->hasAttribute('disabled'))->toBeFalse()
        ->and($items[0]->querySelector('[data-repeater-action=up]')->getAttribute('aria-label'))->toBe('Move Person 1 up')
        ->and($items[1]->querySelector('[data-repeater-action=remove]')->getAttribute('aria-label'))->toBe('Remove Person 2')
        ->and($dom->querySelector('[role=status][aria-live=polite]'))->not->toBeNull();
});

it('disables adding at the maximum and says when there are no rows', function (): void {
    $full = Html::render(Livewire::test(RepeaterComponent::class)->set('items', array_fill(0, 4, ['name' => 'x']))->html());
    $empty = Html::render('<x-ui::repeater label="People" name="items" :rows="[]" add-label="Add person" />');

    expect($full->querySelector('[data-repeater-add]')->hasAttribute('disabled'))->toBeTrue()
        ->and($empty->querySelector('[data-repeater-add]')->hasAttribute('disabled'))->toBeFalse()
        ->and($empty->querySelector('[data-repeater-add]')->textContent)->toContain('Add person')
        ->and($empty->querySelector('ol'))->toBeNull()
        ->and($empty->body->textContent)->toContain('Nothing added yet.');
});

it('reports a list-level error against the group', function (): void {
    view()->share('errors', Html::errors(['items' => 'Add at least one person.']));
    $dom = Html::render('<x-ui::repeater label="People" name="items" :rows="[]" />');

    expect($dom->getElementById('ui-items-error')->textContent)->toBe('Add at least one person.')
        ->and($dom->querySelector('[x-data]')->getAttribute('aria-describedby'))->toBe('ui-items-error');
});

it('refuses a repeater without a name or a label', function (string $template, string $message): void {
    Html::render($template);
})->throws(ViewException::class)->with([
    'a name' => ['<x-ui::repeater label="People" :rows="[]" />', 'name'],
    'a label' => ['<x-ui::repeater name="items" :rows="[]" />', 'label'],
]);

it('refuses a row that has no key', function (): void {
    Html::render('<x-ui::repeater label="People" name="items" :rows="[[\'name\' => \'a\']]"><x-ui::repeater.row :index="0">x</x-ui::repeater.row></x-ui::repeater>');
})->throws(ViewException::class, 'needs a row with a key');

it('puts a tampered key safely into the row attributes', function (): void {
    $key = "x\"'><script>alert(1)</script>";
    $dom = Html::render('<x-ui::repeater label="People" name="items" :rows="$rows"><x-ui::repeater.row :index="0">x</x-ui::repeater.row></x-ui::repeater>', ['rows' => [['_key' => $key]]]);

    expect($dom->querySelector('script'))->toBeNull()
        ->and($dom->querySelector('li')->getAttribute('data-repeater-key'))->toBe($key)
        ->and($dom->querySelector('li')->getAttribute('x-on:drop'))->not->toContain('<script>');
});

it('keeps a row error with its row when rows move, reorder or are removed', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->set('items.1.name', 'x')->call('save');
    $bad = keyOf($component, 1);

    $component->call('moveRepeaterRow', 'items', $bad, 'up');
    expect($component->errors()->keys())->toBe(['items.0.name']);

    $keys = array_column($component->get('items'), '_key');
    $component->call('reorderRepeaterRows', 'items', array_reverse($keys));
    expect($component->errors()->keys())->toBe(['items.2.name']);

    $component->call('removeRepeaterRow', 'items', keyOf($component, 0));
    expect($component->errors()->keys())->toBe(['items.1.name']);

    $component->call('removeRepeaterRow', 'items', $bad);
    expect($component->errors()->keys())->toBe([]);
});

it('keys rows that an action assigns or resets', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->call('loadFromStorage');

    $component->assertSee('Person 1')->assertDontSee('Person 2');
    expect(array_filter(array_column($component->get('items'), '_key')))->toHaveCount(1);

    $component->call('resetItems')->assertSee('Person 3');
    expect(array_filter(array_column($component->get('items'), '_key')))->toHaveCount(3);
});

it('shows a message on the row itself', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->call('saveWithoutKeyAllowance');

    $rows = Html::render($component->html())->querySelectorAll('li[data-repeater-key]');

    expect($rows)->toHaveCount(3)
        ->and($rows[1]->querySelector('p[id$="-error"]')?->textContent)->toContain('items.1')
        ->and($rows[1]->querySelector('[data-repeater-row]')->getAttribute('aria-describedby'))->toBe('ui-items-row-1-title-error');

    Livewire::test(RepeaterComponent::class)->call('saveStrictly')->assertHasNoErrors();
});

it('ignores a reorder holding something other than keys', function (): void {
    $component = Livewire::test(RepeaterComponent::class);
    $before = $component->get('items');

    $component->call('reorderRepeaterRows', 'items', [['x'], 'k', 'j']);

    expect($component->get('items'))->toBe($before);
});

it('takes the minimum and maximum from the spec, not from the markup', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->set('items', [['name' => 'only']]);
    $one = Html::render($component->html());

    $component->set('items', array_fill(0, 4, ['name' => 'x']));
    $four = Html::render($component->html());

    expect($one->querySelector('[data-repeater-action=remove]')->hasAttribute('disabled'))->toBeTrue()
        ->and($one->querySelector('[data-repeater-add]')->hasAttribute('disabled'))->toBeFalse()
        ->and($four->querySelector('[data-repeater-action=remove]')->hasAttribute('disabled'))->toBeFalse()
        ->and($four->querySelector('[data-repeater-add]')->hasAttribute('disabled'))->toBeTrue();
});

it('cuts a property set from the browser to the maximum and pads it to the minimum', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->set('items', array_fill(0, 10, ['name' => 'x']));

    expect($component->get('items'))->toHaveCount(4);

    $component->set('items', []);

    expect($component->get('items'))->toHaveCount(1)
        ->and($component->get('items')[0]['name'])->toBe('')
        ->and($component->get('items')[0]['_key'])->not->toBe('');
});

it('cuts a row update that grows the property past the maximum', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->set('items.7', ['name' => 'x']);

    expect($component->get('items'))->toHaveCount(4);
});

it('answers a render hook called from the browser without failing', function (): void {
    Livewire::test(RepeaterComponent::class)->call('renderingWithRepeater')->assertOk();
});

it('leaves a repeater alone when another property is updated', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->call('loadOverMaximum')->set('untouched', 'changed');

    expect($component->get('items'))->toHaveCount(6)
        ->and(repeaterNames($component->get('items'))[5])->toBe('Stored 6');
});

it('keeps a row error with its row when a browser update reindexes the rows', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->call('loadOverMaximum')->call('save');

    expect($component->errors()->keys())->toBe(['items.2.name']);

    $component->set('items.0', 'junk');

    expect($component->errors()->keys())->toBe(['items.1.name'])
        ->and($component->get('items')[1]['name'])->toBe('');
});

it('keeps row errors in place when a field inside a row is edited', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->set('items.1.name', 'x')->call('save')->set('items.0.name', 'Firstly');

    expect($component->errors()->keys())->toBe(['items.1.name']);
});

it('refuses a repeater whose property is not registered', function (): void {
    try {
        Livewire::test(UnregisteredRepeaterComponent::class);
    } finally {
        Livewire::flushState();
    }
})->throws(ViewException::class, 'is not registered in repeaters()');

it('scopes the row ids to the repeater id', function (): void {
    $dom = Html::render('<x-ui::repeater label="L" name="items" id="first" :rows="$rows"><x-ui::repeater.row :index="0">x</x-ui::repeater.row></x-ui::repeater>', ['rows' => RepeaterRows::withKeys([['name' => 'a']])]);

    expect($dom->getElementById('ui-items-row-0-title'))->not->toBeNull();
});

it('orders the row title through the translation', function (): void {
    app('translator')->addLines(['ui.repeater.title' => '#:number :item'], 'en', 'ui');

    $component = Livewire::test(RepeaterComponent::class);

    expect(Html::render($component->html())->getElementById('ui-items-row-0-title')->textContent)->toBe('#1 Person');
});

it('keeps rows loaded over the maximum when a field inside a row is edited', function (): void {
    $component = Livewire::test(RepeaterComponent::class)->call('loadOverMaximum')->set('items.0.name', 'Edited');

    expect($component->get('items'))->toHaveCount(6)
        ->and(repeaterNames($component->get('items'))[0])->toBe('Edited');
});

it('keeps rows loaded over the maximum when several fields are edited in one request', function (): void {
    $component = Livewire::test(RepeaterComponent::class)
        ->call('loadOverMaximum')
        ->update(updates: ['items.0.name' => 'A', 'items.1.name' => 'B']);

    expect($component->get('items'))->toHaveCount(6)
        ->and(array_slice(repeaterNames($component->get('items')), 0, 2))->toBe(['A', 'B']);
});

it('refuses a spec whose limits contradict each other', function (int $min, ?int $max): void {
    new RepeaterSpec(min: $min, max: $max);
})->with([[3, 2], [-1, null], [0, -1]])->throws(InvalidArgumentException::class);

it('titles a row by the title it is given', function (): void {
    $dom = Html::render('<x-ui::repeater label="L" name="items" :rows="$rows"><x-ui::repeater.row :index="0" title="Mobile">x</x-ui::repeater.row></x-ui::repeater>', ['rows' => RepeaterRows::withKeys([['name' => 'a']])]);

    expect($dom->getElementById('ui-items-row-0-title')->textContent)->toBe('Mobile');
});

it('keeps row ids apart for two repeaters under a wrapper that has an id', function (): void {
    $row = '<x-ui::repeater.row :index="0">x</x-ui::repeater.row>';
    $dom = Html::render('<x-ui::card id="contact"><x-ui::repeater label="Phones" name="phones" :rows="$rows">'.$row.'</x-ui::repeater><x-ui::repeater label="Emails" name="emails" :rows="$rows">'.$row.'</x-ui::repeater></x-ui::card>', ['rows' => [['_key' => 'k']]]);

    expect($dom->getElementById('ui-phones-row-0-title'))->not->toBeNull()
        ->and($dom->getElementById('ui-emails-row-0-title'))->not->toBeNull()
        ->and($dom->getElementById('contact-row-0-title'))->toBeNull();
});

it('leaves out the label element and aria-labelledby when only an aria-label names the group', function (): void {
    $dom = Html::render('<x-ui::repeater aria-label="Phones" name="phones" :rows="[]" />');
    $group = $dom->querySelector('[role=group]');

    expect($group->getAttribute('aria-label'))->toBe('Phones')
        ->and($group->hasAttribute('aria-labelledby'))->toBeFalse()
        ->and($dom->getElementById('ui-phones-label'))->toBeNull();
});

it('marks the row list as a list for browsers that drop list semantics', function (): void {
    $dom = Html::render('<x-ui::repeater label="People" name="items" :rows="$rows"><x-ui::repeater.row :index="0">x</x-ui::repeater.row></x-ui::repeater>', ['rows' => [['_key' => 'k']]]);

    expect($dom->querySelector('ol')->getAttribute('role'))->toBe('list');
});
