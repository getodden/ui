<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Livewire\Livewire;
use Odden\Ui\Enums\RepeaterDirection;
use Odden\Ui\Support\RepeaterRows;
use Odden\Ui\Support\RepeaterSpec;
use Odden\Ui\Tests\Support\Html;
use Odden\Ui\Tests\Support\RepeaterComponent;

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

it('refuses rows that are not arrays when keying and drops them from browser input', function (): void {
    expect(RepeaterRows::onlyRows(['junk', ['name' => 'a'], 3]))->toBe([['name' => 'a']]);

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
    $rows = RepeaterRows::withKeys([['name' => 'a'], ['name' => 'b']]);
    $dom = Html::render(<<<'BLADE'
        <x-ui::repeater label="People" name="items" :rows="$rows" :min="2" :max="3" item-label="Person" hint="Add everyone">
            @foreach ($rows as $index => $row)
                <x-ui::repeater.row :index="$index"><span>content {{ $index }}</span></x-ui::repeater.row>
            @endforeach
        </x-ui::repeater>
    BLADE, ['rows' => $rows]);

    $group = $dom->querySelector('[x-data]');
    $items = $dom->querySelectorAll('ol > li');
    $first = $items[0]->querySelector('[data-repeater-row]');

    expect($group->getAttribute('role'))->toBe('group')
        ->and($group->getAttribute('aria-labelledby'))->toBe('ui-items-label')
        ->and($group->getAttribute('aria-describedby'))->toBe('ui-items-hint')
        ->and($dom->getElementById('ui-items-label')->textContent)->toBe('People')
        ->and($items)->toHaveCount(2)
        ->and($items[0]->getAttribute('data-repeater-key'))->toBe($rows[0]['_key'])
        ->and($first->getAttribute('aria-labelledby'))->toBe('ui-items-row-0-title')
        ->and($dom->getElementById('ui-items-row-1-title')->textContent)->toBe('Person 2')
        ->and($items[0]->querySelector('[data-repeater-action=up]')->hasAttribute('disabled'))->toBeTrue()
        ->and($items[0]->querySelector('[data-repeater-action=down]')->hasAttribute('disabled'))->toBeFalse()
        ->and($items[1]->querySelector('[data-repeater-action=down]')->hasAttribute('disabled'))->toBeTrue()
        ->and($items[0]->querySelector('[data-repeater-action=remove]')->hasAttribute('disabled'))->toBeTrue()
        ->and($items[0]->querySelector('[data-repeater-action=up]')->getAttribute('aria-label'))->toBe('Move Person 1 up')
        ->and($items[1]->querySelector('[data-repeater-action=remove]')->getAttribute('aria-label'))->toBe('Remove Person 2')
        ->and($dom->querySelector('[role=status][aria-live=polite]'))->not->toBeNull();
});

it('disables adding at the maximum and says when there are no rows', function (): void {
    $full = Html::render('<x-ui::repeater label="People" name="items" :rows="[[\'_key\' => \'a\']]" :max="1" />');
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
        ->and($dom->querySelector('li')->getAttribute('x-on:drop.prevent'))->not->toContain('<script>');
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
