<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Odden\Ui\Support\ChartData;
use Odden\Ui\Support\ChartGeometry;
use Odden\Ui\Support\ChartPalette;
use Odden\Ui\Support\ChartScale;
use Odden\Ui\Tests\Support\Html;

function chartSeries(string $name, array $values): array
{
    return ['name' => $name, 'values' => $values];
}

it('gives every chart type its accessible name from the visible or hidden caption', function (string $template): void {
    $dom = Html::render($template);

    $svg = $dom->querySelector('svg');
    $caption = $dom->getElementById($svg->getAttribute('aria-labelledby'));

    expect($svg->getAttribute('role'))->toBe('img')
        ->and($caption->tagName)->toBe('FIGCAPTION')
        ->and($caption->textContent)->toBe('Chart name');
})->with([
    'bar' => ['<x-ui::bar-chart label="Chart name" :categories="[\'a\']" :series="[[\'values\' => [1]]]" />'],
    'line' => ['<x-ui::line-chart label="Chart name" :categories="[\'a\']" :series="[[\'values\' => [1]]]" />'],
    'sparkline' => ['<x-ui::sparkline label="Chart name" :values="[1, 2]" />'],
    'donut' => ['<x-ui::donut-chart label="Chart name" :segments="[[\'label\' => \'a\', \'value\' => 1]]" />'],
]);

it('refuses a chart without a label', function (string $template): void {
    Html::render($template);
})->with([
    '<x-ui::bar-chart :categories="[\'a\']" :series="[[\'values\' => [1]]]" />',
    '<x-ui::line-chart />',
    '<x-ui::sparkline :values="[1]" />',
    '<x-ui::donut-chart />',
    '<x-ui::stat value="1" />',
])->throws(ViewException::class, 'needs a label');

it('hides the caption unless it is asked to be visible', function (): void {
    $hidden = Html::render('<x-ui::bar-chart label="L" :categories="[\'a\']" :series="[[\'values\' => [1]]]" />')->querySelector('figcaption');
    $visible = Html::render('<x-ui::bar-chart label="L" label-visible :categories="[\'a\']" :series="[[\'values\' => [1]]]" />')->querySelector('figcaption');

    expect($hidden->getAttribute('class'))->toContain('sr-only')
        ->and($visible->getAttribute('class'))->not->toContain('sr-only');
});

it('renders a data table with one row per category and a column per series', function (): void {
    $dom = Html::render('<x-ui::bar-chart label="Deals" :categories="[\'Jan\', \'Feb\']" :series="$series" />', [
        'series' => [chartSeries('Won', [4, 1234.5]), chartSeries('Lost', [2, null])],
    ]);

    $headers = array_map(fn ($th) => $th->textContent, iterator_to_array($dom->querySelectorAll('thead th')));
    $rows = array_map(fn ($tr) => array_map(fn ($cell) => $cell->textContent, iterator_to_array($tr->querySelectorAll('th, td'))), iterator_to_array($dom->querySelectorAll('tbody tr')));

    expect($headers)->toBe(['Category', 'Won', 'Lost'])
        ->and($rows)->toBe([['Jan', '4', '2'], ['Feb', '1,234.5', '']]);
});

it('keeps the data table in the page but visually hidden, with a toggle that controls it', function (): void {
    $dom = Html::render('<x-ui::line-chart label="L" :categories="[\'a\', \'b\']" :series="[[\'values\' => [1, 2]]]" />');

    $toggle = $dom->querySelector('button');
    $tableWrapper = $dom->getElementById($toggle->getAttribute('aria-controls'));

    expect($toggle->getAttribute('aria-expanded'))->toBe('false')
        ->and($tableWrapper->getAttribute('class'))->toContain('sr-only')
        ->and($tableWrapper->querySelector('table'))->not->toBeNull()
        ->and($dom->querySelector('figure')->getAttribute('x-data'))->toContain('tableOpen');
});

it('escapes category, series and segment labels everywhere', function (): void {
    $evil = '<img src=x onerror=alert(1)>"&';

    $bars = Html::render('<x-ui::bar-chart :label="$evil" :categories="[$evil]" :series="[[\'name\' => $evil, \'values\' => [1]], [\'name\' => \'b\', \'values\' => [2]]]" />', ['evil' => $evil]);
    $donut = Html::render('<x-ui::donut-chart :label="$evil" :segments="[[\'label\' => $evil, \'value\' => 1]]" />', ['evil' => $evil]);

    foreach ([$bars, $donut] as $dom) {
        expect($dom->querySelector('img'))->toBeNull()
            ->and($dom->querySelector('figure')->textContent)->toContain($evil);
    }
});

it('shows an empty message instead of a chart when there is nothing to plot', function (string $template): void {
    $dom = Html::render($template);

    expect($dom->querySelector('svg'))->toBeNull()
        ->and($dom->querySelector('figure')->textContent)->toContain('No data to show')
        ->and($dom->querySelector('figcaption')->textContent)->toBe('Empty');
})->with([
    'bar without categories' => ['<x-ui::bar-chart label="Empty" :categories="[]" :series="[]" />'],
    'bar with only null values' => ['<x-ui::bar-chart label="Empty" :categories="[\'a\']" :series="[[\'values\' => [null]]]" />'],
    'bar with non numeric values' => ['<x-ui::bar-chart label="Empty" :categories="[\'a\']" :series="[[\'values\' => [\'x\']]]" />'],
    'line without series' => ['<x-ui::line-chart label="Empty" :categories="[\'a\']" :series="[]" />'],
    'donut with zero and negative segments' => ['<x-ui::donut-chart label="Empty" :segments="[[\'label\' => \'a\', \'value\' => 0], [\'label\' => \'b\', \'value\' => -3]]" />'],
    'donut with nothing' => ['<x-ui::donut-chart label="Empty" />'],
    'sparkline with nothing' => ['<x-ui::sparkline label="Empty" :values="[]" />'],
    'sparkline with only nulls' => ['<x-ui::sparkline label="Empty" :values="[null, null]" />'],
]);

it('draws one rect per value for vertical, horizontal and stacked bars', function (array $attributes): void {
    $dom = Html::render('<x-ui::bar-chart label="L" :categories="[\'a\', \'b\', \'c\']" :series="$series" '.$attributes['markup'].' />', [
        'series' => [chartSeries('One', [1, 2, 3]), chartSeries('Two', [3, null, 1])],
    ]);

    expect($dom->querySelectorAll('svg rect'))->toHaveCount(5);
})->with([
    'vertical' => [['markup' => '']],
    'horizontal' => [['markup' => 'orientation="horizontal"']],
    'stacked' => [['markup' => 'stacked']],
]);

it('gives a single bar a visible height', function (): void {
    $bars = ChartGeometry::bars(['a', 'b'], [chartSeries('S', [1000000.0, 0.001])], false, false)['bars'];

    expect($bars[1]['height'])->toBeGreaterThanOrEqual(1.0);
});

it('stacks positive and negative values away from the baseline', function (): void {
    $geometry = ChartGeometry::bars(['a'], [chartSeries('P', [4.0]), chartSeries('Q', [3.0]), chartSeries('N', [-2.0])], false, true);
    [$first, $second, $negative] = $geometry['bars'];

    expect($second['y'] + $second['height'])->toEqualWithDelta($first['y'], 0.01)
        ->and($negative['y'])->toEqualWithDelta($geometry['baseline'], 0.01)
        ->and($geometry['ticks'][0]['label'])->toBe('-2');
});

it('puts bars side by side when not stacked and on top of each other when stacked', function (): void {
    $series = [chartSeries('A', [2.0]), chartSeries('B', [3.0])];

    $grouped = ChartGeometry::bars(['a'], $series, false, false)['bars'];
    $stacked = ChartGeometry::bars(['a'], $series, false, true)['bars'];

    expect($grouped[0]['x'])->not->toBe($grouped[1]['x'])
        ->and($stacked[0]['x'])->toBe($stacked[1]['x']);
});

it('draws a horizontal chart taller for more categories', function (): void {
    $few = ChartGeometry::bars(['a'], [chartSeries('S', [1.0])], true, false);
    $many = ChartGeometry::bars(array_fill(0, 10, 'x'), [chartSeries('S', array_fill(0, 10, 1.0))], true, false);

    expect($many['height'])->toBeGreaterThan($few['height']);
});

it('keeps all bars inside the plot area', function (): void {
    $geometry = ChartGeometry::bars(['a', 'b', 'c'], [chartSeries('S', [5.0, -3.0, 0.0])], false, false);

    foreach ($geometry['bars'] as $bar) {
        expect($bar['y'])->toBeGreaterThanOrEqual($geometry['plot']['top'] - 0.01)
            ->and($bar['y'] + $bar['height'])->toBeLessThanOrEqual($geometry['plot']['bottom'] + 1.01);
    }
});

it('thins out axis labels when there are many categories and shortens long ones', function (): void {
    $categories = array_map(fn (int $number): string => 'Category number '.$number, range(1, 60));
    $geometry = ChartGeometry::bars($categories, [chartSeries('S', array_fill(0, 60, 1.0))], false, false);

    expect(count($geometry['labels']))->toBeLessThan(60)
        ->and($geometry['labels'][0]['text'])->toBe('Category numb…')
        ->and($geometry['labels'][0]['full'])->toBe('Category number 1');
});

it('draws a line with a gap where a value is missing', function (): void {
    $line = ChartGeometry::lines(['a', 'b', 'c', 'd', 'e'], [chartSeries('S', [1.0, 2.0, null, 4.0, 5.0])])['lines'][0];

    expect(substr_count($line['path'], 'M'))->toBe(2)
        ->and($line['points'])->toHaveCount(4);
});

it('draws a single point as a marker without a path', function (): void {
    $line = ChartGeometry::lines(['a'], [chartSeries('S', [42.0])])['lines'][0];

    expect($line['path'])->toBeNull()
        ->and($line['points'])->toHaveCount(1)
        ->and($line['points'][0]['marker'])->toBeTrue();
});

it('draws an isolated point between gaps as a marker even for long series', function (): void {
    $values = array_fill(0, 50, 1.0);
    $values[10] = null;
    $values[12] = null;

    $line = ChartGeometry::lines(array_fill(0, 50, 'x'), [chartSeries('S', $values)])['lines'][0];
    $markers = array_filter($line['points'], fn (array $point): bool => $point['marker']);

    expect($markers)->toHaveCount(1);
});

it('draws every series of a line chart in its own colour', function (): void {
    $dom = Html::render('<x-ui::line-chart label="L" :categories="[\'a\', \'b\']" :series="$series" />', [
        'series' => [chartSeries('One', [1, 2]), chartSeries('Two', [2, 1])],
    ]);

    $paints = array_map(fn ($path) => $path->getAttribute('style'), iterator_to_array($dom->querySelectorAll('svg path')));

    expect($paints)->toHaveCount(2)
        ->and($paints[0])->not->toBe($paints[1])
        ->and($dom->querySelectorAll('ul li'))->toHaveCount(2);
});

it('does not collapse a line chart where every value is the same', function (): void {
    $geometry = ChartGeometry::lines(['a', 'b'], [chartSeries('S', [5.0, 5.0])]);

    expect($geometry['lines'][0]['points'][0]['y'])->toBe($geometry['lines'][0]['points'][1]['y'])
        ->and(count($geometry['ticks']))->toBeGreaterThan(1);
});

it('draws a sparkline as a path with its last point marked', function (): void {
    $sparkline = ChartGeometry::sparkline([1.0, null, 3.0, 2.0]);

    expect($sparkline['path'])->toStartWith('M')
        ->and($sparkline['dots'])->toHaveCount(2)
        ->and($sparkline['dots'][0])->toBe(['x' => 3.0, 'y' => 25.0]);
});

it('draws a single sparkline value as a dot and a flat series in the middle', function (): void {
    $single = ChartGeometry::sparkline([7.0]);
    $flat = ChartGeometry::sparkline([2.0, 2.0, 2.0]);

    expect($single['path'])->toBeNull()
        ->and($single['dots'])->toHaveCount(1)
        ->and($flat['path'])->toContain('14');
});

it('gives each donut segment its share and keeps the arcs in order', function (): void {
    $donut = ChartGeometry::donut([['label' => 'A', 'value' => 1.0], ['label' => 'B', 'value' => 3.0]]);

    expect($donut['total'])->toBe(4.0)
        ->and(array_column($donut['segments'], 'share'))->toBe(['25%', '75%'])
        ->and($donut['segments'][0]['path'])->toStartWith('M60 4');
});

it('draws a donut with one segment as a complete ring', function (): void {
    $dom = Html::render('<x-ui::donut-chart label="L" :segments="[[\'label\' => \'Only\', \'value\' => 5]]" />');

    $path = $dom->querySelector('svg path')->getAttribute('d');

    expect($dom->querySelectorAll('svg path'))->toHaveCount(1)
        ->and(substr_count($path, 'a'))->toBe(4)
        ->and($dom->querySelector('ul li')->textContent)->toContain('Only: 5 (100%)');
});

it('ignores zero, negative and malformed donut segments', function (): void {
    $dom = Html::render('<x-ui::donut-chart label="L" :segments="$segments" />', [
        'segments' => [['label' => 'A', 'value' => 2], ['label' => 'Zero', 'value' => 0], ['label' => 'Negative', 'value' => -4], 'junk', ['label' => 'Text', 'value' => 'abc'], ['label' => 'B', 'value' => '2']],
    ]);

    expect($dom->querySelectorAll('svg path'))->toHaveCount(2)
        ->and($dom->querySelector('figure')->textContent)->not->toContain('Negative');
});

it('reads numbers and refuses what is not finite', function (): void {
    expect(ChartData::number(3))->toBe(3.0)
        ->and(ChartData::number('4.5'))->toBe(4.5)
        ->and(ChartData::number('abc'))->toBeNull()
        ->and(ChartData::number(NAN))->toBeNull()
        ->and(ChartData::number(INF))->toBeNull()
        ->and(ChartData::number(null))->toBeNull()
        ->and(ChartData::number([1]))->toBeNull();
});

it('caps the number of categories and series', function (): void {
    $categories = ChartData::categories(range(1, 1000));
    $series = ChartData::series(array_fill(0, 30, ['values' => [1]]), 1);

    expect($categories)->toHaveCount(ChartData::MAXIMUM_CATEGORIES)
        ->and($series)->toHaveCount(ChartData::MAXIMUM_SERIES);
});

it('names unnamed series', function (): void {
    expect(array_column(ChartData::series([['values' => [1]]], 1), 'name'))->toBe(['Value'])
        ->and(array_column(ChartData::series([['values' => [1]], ['values' => [2]]], 1), 'name'))->toBe(['Series 1', 'Series 2']);
});

it('pads short series and ignores extra values', function (): void {
    $series = ChartData::series([['name' => 'S', 'values' => [1, 2, 3, 4]]], 2);

    expect($series[0]['values'])->toBe([1.0, 2.0]);
    expect(ChartData::series([['name' => 'S', 'values' => [1]]], 3)[0]['values'])->toBe([1.0, null, null]);
});

it('picks round tick values that cover the data', function (float $minimum, float $maximum, array $expected): void {
    expect(ChartScale::ticks($minimum, $maximum))->toBe($expected);
})->with([
    'zero to seven' => [0.0, 7.0, [0.0, 2.0, 4.0, 6.0, 8.0]],
    'negative to positive' => [-3.0, 12.0, [-5.0, 0.0, 5.0, 10.0, 15.0]],
    'fractions' => [0.0, 0.5, [0.0, 0.1, 0.2, 0.3, 0.4, 0.5]],
    'nothing' => [0.0, 0.0, [0.0, 0.2, 0.4, 0.6, 0.8, 1.0]],
    'large' => [0.0, 1200000.0, [0.0, 500000.0, 1000000.0, 1500000.0]],
]);

it('formats numbers compactly for axes and fully for tables', function (): void {
    expect(ChartScale::format(1234.5))->toBe('1,234.5')
        ->and(ChartScale::format(-0.0))->toBe('0')
        ->and(ChartScale::format(2.0))->toBe('2')
        ->and(ChartScale::compact(1500.0))->toBe('1.5K')
        ->and(ChartScale::compact(2000000.0))->toBe('2M')
        ->and(ChartScale::compact(-3000.0))->toBe('-3K')
        ->and(ChartScale::compact(12.0))->toBe('12');
});

it('renders a stat with its label, value and delta direction as text', function (string $delta, ?string $direction, string $word, string $colour): void {
    $dom = Html::render('<x-ui::stat label="Contacts" value="1,248" :delta="$delta" :direction="$direction" delta-label="vs last month" />', ['delta' => $delta, 'direction' => $direction]);

    $deltaText = $dom->querySelectorAll('dd')[1];

    expect($dom->querySelector('dt')->textContent)->toBe('Contacts')
        ->and($dom->querySelectorAll('dd')[0]->textContent)->toBe('1,248')
        ->and($deltaText->querySelector('.sr-only')->textContent)->toBe($word)
        ->and($deltaText->textContent)->toContain($delta, 'vs last month')
        ->and($deltaText->querySelector('svg')->getAttribute('aria-hidden'))->toBe('true')
        ->and($deltaText->innerHTML)->toContain($colour);
})->with([
    'inferred up' => ['+12%', null, 'Up', 'text-emerald-700'],
    'inferred down' => ['-3%', null, 'Down', 'text-rose-700'],
    'inferred down with a minus sign' => ['−3%', null, 'Down', 'text-rose-700'],
    'inferred flat' => ['0%', null, 'No change', 'text-gray-600'],
    'explicit down' => ['4%', 'down', 'Down', 'text-rose-700'],
    'unknown direction falls back to the sign' => ['+4%', 'sideways', 'Up', 'text-emerald-700'],
]);

it('lets a stat say that down is good', function (): void {
    $delta = Html::render('<x-ui::stat label="Churn" value="2%" delta="-0.4%" sentiment="positive" />')->querySelectorAll('dd')[1];

    expect($delta->querySelector('.sr-only')->textContent)->toBe('Down')
        ->and($delta->innerHTML)->toContain('text-emerald-700');
});

it('falls back to the direction for an unknown stat sentiment', function (): void {
    $delta = Html::render('<x-ui::stat label="Churn" value="2%" delta="+1" sentiment="great" />')->querySelectorAll('dd')[1];

    expect($delta->innerHTML)->toContain('text-emerald-700');
});

it('renders a stat without a delta or sparkline as just label and value', function (): void {
    $dom = Html::render('<x-ui::stat label="Contacts" :value="0" />');

    expect($dom->querySelectorAll('dd'))->toHaveCount(1)
        ->and($dom->querySelectorAll('dd')[0]->textContent)->toBe('0')
        ->and($dom->querySelector('svg'))->toBeNull();
});

it('escapes the stat label, value and delta', function (): void {
    $dom = Html::render('<x-ui::stat :label="$evil" :value="$evil" :delta="$evil" />', ['evil' => '<b id="x">hi</b>']);

    expect($dom->querySelector('#x'))->toBeNull();
});

it('names the sparkline inside a stat after the stat and gives it a table', function (): void {
    $dom = Html::render('<x-ui::stat label="Contacts" value="9" :sparkline="[1, 2, 3]" />');

    $svg = $dom->querySelector('svg');

    expect($dom->getElementById($svg->getAttribute('aria-labelledby'))->textContent)->toBe('Contacts, trend')
        ->and($dom->querySelectorAll('tbody tr'))->toHaveCount(3);
});

it('passes attributes through to the chart root', function (): void {
    $figure = Html::render('<x-ui::donut-chart label="L" id="mine" class="mt-4" :segments="[[\'label\' => \'a\', \'value\' => 1]]" />')->querySelector('figure');

    expect($figure->getAttribute('id'))->toBe('mine')
        ->and($figure->getAttribute('class'))->toContain('mt-4')
        ->and($figure->querySelector('table')->parentNode->parentNode->getAttribute('id'))->toBe('mine-table');
});

it('terminates and spans the data when the lowest and highest values differ only by rounding', function (float $lowest, float $highest): void {
    $ticks = ChartScale::ticks($lowest, $highest);

    expect(count($ticks))->toBeLessThan(20)
        ->and($ticks[0])->toBeLessThanOrEqual($lowest)
        ->and($ticks[array_key_last($ticks)])->toBeGreaterThanOrEqual($highest);
})->with([
    'float sum' => [0.1 + 0.2, 0.3],
    'huge' => [1e17, 1e17 + 16],
    'one ulp' => [1.0, 1.0 + PHP_FLOAT_EPSILON],
]);

it('draws a line chart whose values differ only by rounding', function (): void {
    $dom = Html::render('<x-ui::line-chart label="Avg" :categories="[\'a\', \'b\']" :series="[[\'values\' => [0.1 + 0.2, 0.3]]]" />');

    expect($dom->querySelectorAll('svg circle'))->toHaveCount(2);
});

it('keeps grouped bars visible with many categories and series', function (int $categories, int $series): void {
    $names = array_map(fn (int $number): string => 'c'.$number, range(1, $categories));
    $data = array_map(fn (int $number): array => chartSeries('s'.$number, array_fill(0, $categories, 5.0)), range(1, $series));

    $geometry = ChartGeometry::bars($names, $data, false, false);

    foreach ($geometry['bars'] as $bar) {
        expect($bar['width'])->toBeGreaterThan(0.0);
    }

    expect($geometry['bars'])->toHaveCount($categories * $series);
})->with([
    'a month of days with six series' => [30, 6],
    'a year with the most series' => [12, ChartData::MAXIMUM_SERIES],
    'the most categories' => [ChartData::MAXIMUM_CATEGORIES, 6],
]);

it('lays grouped bars side by side without overlap', function (): void {
    $bars = ChartGeometry::bars(['a'], [chartSeries('A', [1.0]), chartSeries('B', [1.0]), chartSeries('C', [1.0])], false, false)['bars'];

    expect($bars[0]['x'] + $bars[0]['width'])->toBeLessThanOrEqual($bars[1]['x'] + 0.01)
        ->and($bars[1]['x'] + $bars[1]['width'])->toBeLessThanOrEqual($bars[2]['x'] + 0.01);
});

it('keeps small values readable on axes, tooltips and in the data table', function (): void {
    $geometry = ChartGeometry::lines(['a', 'b', 'c'], [chartSeries('Rate', [0.001, 0.0125, 0.005])]);
    $labels = array_column($geometry['ticks'], 'label');

    expect(count(array_unique($labels)))->toBe(count($labels))
        ->and(array_column($geometry['lines'][0]['points'], 'title'))->toBe(['a, Rate: 0.001', 'b, Rate: 0.0125', 'c, Rate: 0.005'])
        ->and(ChartData::tableRows(['a'], [chartSeries('Rate', [0.004])]))->toBe([['a', '0.004']])
        ->and(ChartScale::format(-0.001))->toBe('-0.001')
        ->and(ChartScale::format(0.30000000000000004))->toBe('0.3')
        ->and(ChartScale::format(1.239))->toBe('1.24')
        ->and(ChartScale::format(1.0E-15))->toBe('0');
});

it('gives every series a colour of its own', function (): void {
    $paints = array_map(ChartPalette::paint(...), range(0, ChartData::MAXIMUM_SERIES - 1));

    expect(array_unique($paints))->toHaveCount(ChartData::MAXIMUM_SERIES);
});

it('keeps a tiny bar on the correct side of the baseline', function (): void {
    $vertical = ChartGeometry::bars(['a', 'b', 'c', 'd'], [chartSeries('S', [10.0, 0.001, 0.0, -0.001])], false, false);
    [, $tiny, $zero, $tinyNegative] = $vertical['bars'];

    expect($tiny['y'] + $tiny['height'])->toBeLessThanOrEqual($vertical['baseline'] + 0.01)
        ->and($zero['y'] + $zero['height'])->toBeLessThanOrEqual($vertical['baseline'] + 0.01)
        ->and($tinyNegative['y'])->toBeGreaterThanOrEqual($vertical['baseline'] - 0.01);

    $horizontal = ChartGeometry::bars(['a', 'b'], [chartSeries('S', [10.0, -0.001])], true, false);

    expect($horizontal['bars'][1]['x'] + $horizontal['bars'][1]['width'])->toBeLessThanOrEqual($horizontal['baseline'] + 0.01);

    $positiveHorizontal = ChartGeometry::bars(['a', 'b'], [chartSeries('S', [-10.0, 0.001])], true, false);

    expect($positiveHorizontal['bars'][1]['x'])->toBeGreaterThanOrEqual($positiveHorizontal['baseline'] - 0.01);
});

it('keeps valid donut segments that follow ignored ones and names unlabelled segments', function (): void {
    $junk = array_fill(0, 12, ['label' => 'Zero', 'value' => 0]);

    $dom = Html::render('<x-ui::donut-chart label="L" :segments="$segments" />', [
        'segments' => [...$junk, ['label' => 'Web', 'value' => 40], ['value' => 5]],
    ]);

    $items = array_map(fn ($li) => trim($li->textContent), iterator_to_array($dom->querySelectorAll('ul li')));

    expect($items)->toBe(['Web: 40 (88.9%)', 'Segment 2: 5 (11.1%)'])
        ->and($dom->querySelector('tbody tr:last-child th')->textContent)->toBe('Segment 2');
});

it('caps donut segments at the palette size after dropping ignored ones', function (): void {
    $segments = array_map(fn (int $number): array => ['label' => 's'.$number, 'value' => $number], range(0, 10));

    $dom = Html::render('<x-ui::donut-chart label="L" :segments="$segments" />', ['segments' => $segments]);

    expect($dom->querySelectorAll('ul li'))->toHaveCount(ChartData::MAXIMUM_SERIES);
});

it('names each data table toggle after its chart', function (): void {
    $dom = Html::render('<x-ui::bar-chart label="Deals by month" :categories="[\'a\']" :series="[[\'values\' => [1]]]" />');

    $button = $dom->querySelector('button');

    expect($button->textContent)->toContain('Show data table', 'Deals by month');
});
