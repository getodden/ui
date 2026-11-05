<?php

declare(strict_types=1);

use Odden\Ui\Support\RichJson;

function paragraph(array ...$children): array
{
    return ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => $children]]];
}

it('keeps a document as the editor writes it', function (): void {
    $document = [
        'type' => 'doc',
        'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Title']]],
            ['type' => 'paragraph', 'content' => [
                ['type' => 'text', 'text' => 'Hi '],
                ['type' => 'mention', 'attrs' => ['id' => '7', 'label' => 'Ada Lovelace', 'char' => '@']],
                ['type' => 'hardBreak'],
                ['type' => 'text', 'marks' => [['type' => 'bold'], ['type' => 'italic']], 'text' => 'both'],
                ['type' => 'text', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://example.com', 'target' => '_blank', 'rel' => 'noopener noreferrer nofollow', 'class' => null]]], 'text' => 'link'],
                ['type' => 'text', 'marks' => [['type' => 'link', 'attrs' => ['href' => '/relative', 'target' => '_blank', 'rel' => 'noopener noreferrer nofollow', 'class' => null]]], 'text' => 'relative'],
            ]],
            ['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'a']]]]]]],
            ['type' => 'orderedList', 'attrs' => ['start' => 3, 'type' => 'a'], 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph']]]]],
            ['type' => 'blockquote', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'q']]]]],
            ['type' => 'codeBlock', 'attrs' => ['language' => 'php'], 'content' => [['type' => 'text', 'text' => 'echo 1;']]],
            ['type' => 'horizontalRule'],
        ],
    ];

    expect(RichJson::clean($document))->toEqual($document);
});

it('keeps a Cloud-shaped mention paragraph with a hard break', function (): void {
    $document = paragraph(
        ['type' => 'text', 'text' => 'Dear '],
        ['type' => 'mention', 'attrs' => ['id' => 42, 'label' => 'Grace', 'char' => '@']],
        ['type' => 'hardBreak'],
        ['type' => 'text', 'text' => 'Thanks'],
    );

    expect(RichJson::clean($document)['content'][0]['content'][1])->toBe(['type' => 'mention', 'attrs' => ['id' => '42', 'label' => 'Grace', 'char' => '@']]);
});

it('drops unsafe link marks but keeps their text', function (string $href): void {
    $clean = RichJson::clean(paragraph(['type' => 'text', 'text' => 'click', 'marks' => [['type' => 'link', 'attrs' => ['href' => $href]]]]));

    expect($clean['content'][0]['content'])->toBe([['type' => 'text', 'text' => 'click']]);
})->with(['javascript:alert(1)', "jav\tascript:alert(1)", ' JaVaScRiPt:alert(1)', 'data:text/html,x', 'vbscript:x', '//evil.example', 'relative', '']);

it('fixes the link attributes', function (): void {
    $clean = RichJson::clean(paragraph(['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://example.com', 'target' => 'javascript:x', 'rel' => 'opener', 'class' => 'evil', 'onclick' => 'x']]]]));

    expect($clean['content'][0]['content'][0]['marks'][0]['attrs'])->toBe(['href' => 'https://example.com', 'target' => null, 'rel' => null, 'class' => null]);
});

it('replaces unknown nodes by their text and drops unknown marks and attributes', function (): void {
    $document = ['type' => 'doc', 'content' => [
        ['type' => 'table', 'content' => [['type' => 'tableRow', 'content' => [['type' => 'tableCell', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'cell']]]]]]]]],
        ['type' => 'image', 'attrs' => ['src' => 'javascript:x']],
        ['type' => 'paragraph', 'attrs' => ['textAlign' => 'center', 'onclick' => 'x'], 'content' => [
            ['type' => 'text', 'text' => 'styled', 'marks' => [['type' => 'textStyle', 'attrs' => ['color' => 'red']], ['type' => 'bold', 'attrs' => ['x' => 1]]]],
        ]],
    ]];

    expect(RichJson::clean($document))->toBe(['type' => 'doc', 'content' => [
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'cell']]],
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'styled', 'marks' => [['type' => 'bold']]]]],
    ]]);
});

it('normalises attributes to the allowed values', function (array $node, array $expected): void {
    expect(RichJson::clean(['type' => 'doc', 'content' => [$node]])['content'][0])->toBe($expected);
})->with([
    'heading level out of range' => [['type' => 'heading', 'attrs' => ['level' => 9], 'content' => [['type' => 'text', 'text' => 'x']]], ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x']]]],
    'heading level string' => [['type' => 'heading', 'attrs' => ['level' => '2'], 'content' => [['type' => 'text', 'text' => 'x']]], ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x']]]],
    'heading one' => [['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'x']]], ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'x']]]],
    'bad list start and type' => [['type' => 'orderedList', 'attrs' => ['start' => '3', 'type' => 'z'], 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph']]]]], ['type' => 'orderedList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph']]]]]],
    'bad code language' => [['type' => 'codeBlock', 'attrs' => ['language' => 'a" onclick="x'], 'content' => [['type' => 'text', 'text' => 'x']]], ['type' => 'codeBlock', 'content' => [['type' => 'text', 'text' => 'x']]]],
    'mention without id becomes text' => [['type' => 'paragraph', 'content' => [['type' => 'mention', 'attrs' => ['label' => 'Ada']]]], ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '@Ada']]]],
    'mention with a long char' => [['type' => 'paragraph', 'content' => [['type' => 'mention', 'attrs' => ['id' => 1, 'label' => 'Ada', 'char' => '@@@', 'onclick' => 'x']]]], ['type' => 'paragraph', 'content' => [['type' => 'mention', 'attrs' => ['id' => '1', 'label' => 'Ada', 'char' => '@']]]]],
    'mention with an array label is dropped' => [['type' => 'paragraph', 'content' => [['type' => 'mention', 'attrs' => ['id' => 1, 'label' => ['x']]]]], ['type' => 'paragraph']],
    'text that is not a string is dropped' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => ['x']], ['type' => 'text', 'text' => 5]]], ['type' => 'paragraph']],
    'inline content wrapped inside a list' => [['type' => 'bulletList', 'content' => [['type' => 'text', 'text' => 'loose']]], ['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'loose']]]]]]]],
    'blocks inside a paragraph are flattened' => [['type' => 'paragraph', 'content' => [['type' => 'blockquote', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'inner']]]]]]], ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'inner']]]],
    'marks stripped in a code block' => [['type' => 'codeBlock', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'bold']]]]], ['type' => 'codeBlock', 'content' => [['type' => 'text', 'text' => 'x']]]],
]);

it('produces a document from garbage', function (mixed $garbage): void {
    expect(RichJson::clean($garbage))->toBe(['type' => 'doc', 'content' => [['type' => 'paragraph']]]);
})->with([
    'empty' => [[]],
    'no type' => [['content' => 'x']],
    'unknown only' => [['type' => 'script', 'content' => 'alert(1)']],
]);

it('refuses documents that are too deep or have too many nodes', function (): void {
    $deep = ['type' => 'paragraph'];

    for ($level = 0; $level < RichJson::MAXIMUM_DEPTH + 5; $level++) {
        $deep = ['type' => 'blockquote', 'content' => [$deep]];
    }

    $wide = ['type' => 'doc', 'content' => array_fill(0, RichJson::MAXIMUM_NODES + 1, ['type' => 'horizontalRule'])];

    expect(fn () => RichJson::clean(['type' => 'doc', 'content' => [$deep]]))->toThrow(LengthException::class)
        ->and(fn () => RichJson::clean($wide))->toThrow(LengthException::class);
});

it('settles on a fixed point', function (): void {
    $document = ['type' => 'doc', 'content' => [
        ['type' => 'text', 'text' => 'loose'],
        ['type' => 'bulletList', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'a', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:x']]]]]]]],
        ['type' => 'mention', 'attrs' => ['id' => 1, 'label' => 'A']],
    ]];

    $clean = RichJson::clean($document);

    expect(RichJson::clean($clean))->toBe($clean);
});

it('keeps the rel tokens that are safe and nothing else', function (): void {
    $clean = RichJson::clean(paragraph(['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://example.com', 'target' => '_blank', 'rel' => 'noopener evil NoReferrer']]]]));

    expect($clean['content'][0]['content'][0]['marks'][0]['attrs'])->toBe(['href' => 'https://example.com', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'class' => null]);
});

it('shapes documents the editor schema accepts', function (array $node, array $expected): void {
    expect(RichJson::clean(['type' => 'doc', 'content' => [$node]])['content'])->toBe($expected);
})->with([
    'a nested doc is a wrapper' => [['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'in']]]]], [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'in']]]]],
    'a list item starts with a paragraph' => [['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph']]]]]]]]], [['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph'], ['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph']]]]]]]]]]],
    'empty lists are dropped' => [['type' => 'bulletList', 'content' => []], [['type' => 'paragraph']]],
    'a list item outside a list is unwrapped' => [['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x']]]]], [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x']]]]],
    'code excludes the other marks' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'bold'], ['type' => 'code'], ['type' => 'link', 'attrs' => ['href' => 'https://example.com']]]]]], [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'code']]]]]]],
    'paragraphs in an unknown wrapper stay separate' => [['type' => 'wrapper', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'inner']]], ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'li']]]]], [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'inner']]], ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'li']]]]],
]);

it('treats a top-level list of nodes as the content of the document', function (): void {
    expect(RichJson::clean([['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x']]]]))->toBe(['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x']]]]]);
});

dataset('list shapes', [
    'a heading directly in an ordered list' => [['type' => 'doc', 'content' => [['type' => 'orderedList', 'content' => [['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'h']]]]]]]],
    'a list directly in a list' => [['type' => 'doc', 'content' => [['type' => 'bulletList', 'content' => [['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph']]]]]]]]]],
]);

it('starts every list item with a paragraph, also when it wraps a loose child', function (array $document): void {
    $clean = RichJson::clean($document);

    foreach ($clean['content'][0]['content'] as $item) {
        expect($item['type'])->toBe('listItem')
            ->and($item['content'][0]['type'])->toBe('paragraph');
    }

    expect(RichJson::clean($clean))->toBe($clean);
})->with('list shapes');

it('produces documents the editor schema accepts for every hostile document', function (): void {
    $documents = [
        ['type' => 'doc', 'content' => [['type' => 'orderedList', 'content' => [['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'h']]]]]]],
        ['type' => 'doc', 'content' => [['type' => 'bulletList', 'content' => [['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph']]]]]]]]],
        ['type' => 'doc', 'content' => [['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'a']]], ['type' => 'listItem'], ['type' => 'bulletList'], ['type' => 'orderedList', 'content' => [['type' => 'paragraph']]]]],
        ['type' => 'doc', 'content' => [['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'h']]]]]]]]],
        ['type' => 'doc', 'content' => [['type' => 'table', 'content' => [['type' => 'tableRow', 'content' => [['type' => 'tableCell', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'c']]]]]]]]], ['type' => 'image'], ['type' => 'text', 'text' => 'loose', 'marks' => [['type' => 'bold'], ['type' => 'code']]]]],
        ['type' => 'doc', 'content' => [['type' => 'codeBlock', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'bold']]], ['type' => 'hardBreak'], ['type' => 'mention', 'attrs' => ['id' => 1, 'label' => 'A']]]]]],
        ['type' => 'doc', 'content' => [['type' => 'blockquote', 'content' => [['type' => 'text', 'text' => 'a'], ['type' => 'blockquote']]], ['type' => 'paragraph', 'content' => [['type' => 'blockquote', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'q']]]]], ['type' => 'hardBreak']]]]],
        ['type' => 'doc', 'content' => [['type' => 'orderedList', 'attrs' => ['start' => 4, 'type' => 'i'], 'content' => [['type' => 'text', 'text' => 'a'], ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'b']]]]]]],
        ['type' => 'doc'],
        [],
        [['type' => 'text', 'text' => 'x']],
    ];

    $path = tempnam(sys_get_temp_dir(), 'rich-json');
    file_put_contents($path, json_encode(array_map(fn (array $document): array => RichJson::clean($document), $documents)));

    $output = shell_exec('node --no-warnings '.escapeshellarg(dirname(__DIR__).'/Support/EditorSchemaCheck.mjs').' '.escapeshellarg($path).' 2>&1');
    unlink($path);

    expect(trim((string) $output))->toBe('ok '.count($documents));
})->skip(fn (): bool => ! is_dir(dirname(__DIR__, 2).'/node_modules/@tiptap/core'));
