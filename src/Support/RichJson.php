<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

use LengthException;

final class RichJson
{
    public const MAXIMUM_NODES = 10000;

    public const MAXIMUM_DEPTH = 100;

    private const TEXTBLOCKS = ['paragraph', 'heading', 'codeBlock'];

    private const CONTAINERS = ['doc', 'listItem', 'blockquote'];

    private const LISTS = ['bulletList', 'orderedList'];

    private const BLOCKS = ['paragraph', 'heading', 'bulletList', 'orderedList', 'listItem', 'blockquote', 'codeBlock', 'horizontalRule'];

    private const INLINE = ['text', 'hardBreak', 'mention'];

    private const SIMPLE_MARKS = ['bold', 'italic', 'underline', 'strike', 'code'];

    private const ORDERED_LIST_TYPES = ['1', 'a', 'A', 'i', 'I'];

    private int $nodes = 0;

    /**
     * @param  array<mixed>  $document
     * @return array<string, mixed>
     */
    public static function clean(array $document): array
    {
        $cleaner = new self;
        $children = $cleaner->node($document, 0);

        if (count($children) === 1 && ($children[0]['type'] ?? null) === 'doc') {
            return $children[0];
        }

        return ['type' => 'doc', 'content' => $cleaner->blocks($children)];
    }

    /**
     * @param  array<mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function node(array $node, int $depth): array
    {
        if ($depth > self::MAXIMUM_DEPTH) {
            throw new LengthException('The document is nested deeper than RichJson::clean() accepts.');
        }

        if (++$this->nodes > self::MAXIMUM_NODES) {
            throw new LengthException('The document has more nodes than RichJson::clean() accepts.');
        }

        $type = is_string($node['type'] ?? null) ? $node['type'] : '';

        return match (true) {
            $type === 'text' => $this->text($node),
            $type === 'hardBreak' || $type === 'horizontalRule' => [['type' => $type]],
            $type === 'mention' => $this->mention($node),
            in_array($type, ['doc', ...self::BLOCKS], true) => $this->container($type, $node, $depth),
            default => $this->descendantText($node, $depth),
        };
    }

    /**
     * @param  array<mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function text(array $node): array
    {
        $text = $node['text'] ?? null;

        if (! is_string($text) || $text === '') {
            return [];
        }

        $clean = ['type' => 'text', 'text' => $text];
        $marks = $this->marks($node['marks'] ?? null);

        if ($marks !== []) {
            $clean['marks'] = $marks;
        }

        return [$clean];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function marks(mixed $marks): array
    {
        if (! is_array($marks)) {
            return [];
        }

        $clean = [];

        foreach ($marks as $mark) {
            if (! is_array($mark)) {
                continue;
            }

            $type = is_string($mark['type'] ?? null) ? $mark['type'] : '';

            if (in_array($type, self::SIMPLE_MARKS, true)) {
                $clean[$type] = ['type' => $type];
            } elseif ($type === 'link') {
                $link = $this->link($mark);

                if ($link !== null) {
                    $clean[$type] = $link;
                }
            }
        }

        return array_values($clean);
    }

    /**
     * @param  array<mixed>  $mark
     * @return array<string, mixed>|null
     */
    private function link(array $mark): ?array
    {
        $attributes = is_array($mark['attrs'] ?? null) ? $mark['attrs'] : [];
        $href = is_string($attributes['href'] ?? null) ? SafeUri::clean($attributes['href']) : null;

        if ($href === null) {
            return null;
        }

        return [
            'type' => 'link',
            'attrs' => [
                'href' => $href,
                'target' => ($attributes['target'] ?? null) === '_blank' ? '_blank' : null,
                'rel' => 'noopener noreferrer nofollow',
                'class' => null,
            ],
        ];
    }

    /**
     * @param  array<mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function mention(array $node): array
    {
        $attributes = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
        $id = $attributes['id'] ?? null;
        $label = $attributes['label'] ?? null;
        $char = $attributes['char'] ?? '@';

        if (! is_string($label)) {
            return [];
        }

        $char = is_string($char) && mb_strlen($char) === 1 ? $char : '@';

        if (! is_string($id) && ! is_int($id)) {
            return $this->text(['text' => $char.$label]);
        }

        return [['type' => 'mention', 'attrs' => ['id' => (string) $id, 'label' => $label, 'char' => $char]]];
    }

    /**
     * @param  array<mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function container(string $type, array $node, int $depth): array
    {
        $attributes = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];

        if ($type === 'heading' && ! (is_int($attributes['level'] ?? null) && $attributes['level'] >= 1 && $attributes['level'] <= 4)) {
            $type = 'paragraph';
        }

        $children = $this->children($node, $depth);

        if (in_array($type, self::TEXTBLOCKS, true)) {
            $children = $this->inlineOnly($children, $type);
        } elseif (in_array($type, self::LISTS, true)) {
            $children = $this->listItems($children);
        } elseif (in_array($type, self::CONTAINERS, true)) {
            $children = $this->blocks($children);
        }

        $clean = ['type' => $type];
        $cleanAttributes = $this->attributes($type, $attributes);

        if ($cleanAttributes !== []) {
            $clean['attrs'] = $cleanAttributes;
        }

        if ($children !== []) {
            $clean['content'] = $children;
        }

        return [$clean];
    }

    /**
     * @param  array<mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributes(string $type, array $attributes): array
    {
        $clean = [];

        if ($type === 'heading') {
            $clean['level'] = $attributes['level'];
        }

        if ($type === 'orderedList') {
            if (is_int($attributes['start'] ?? null) && $attributes['start'] >= 1) {
                $clean['start'] = $attributes['start'];
            }

            if (array_key_exists('type', $attributes) && ($attributes['type'] === null || in_array($attributes['type'], self::ORDERED_LIST_TYPES, true))) {
                $clean['type'] = $attributes['type'];
            }
        }

        if ($type === 'codeBlock' && array_key_exists('language', $attributes)) {
            $language = $attributes['language'];

            if ($language === null || (is_string($language) && preg_match('/^[A-Za-z0-9_+-]{1,32}$/', $language) === 1)) {
                $clean['language'] = $language;
            }
        }

        return $clean;
    }

    /**
     * @param  array<mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function children(array $node, int $depth): array
    {
        $content = $node['content'] ?? null;
        $children = [];

        if (! is_array($content)) {
            return [];
        }

        foreach ($content as $child) {
            if (is_array($child)) {
                array_push($children, ...$this->node($child, $depth + 1));
            }
        }

        return $children;
    }

    /**
     * @param  array<mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function descendantText(array $node, int $depth): array
    {
        return $this->flatten($this->children($node, $depth));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function flatten(array $nodes): array
    {
        $inline = [];

        foreach ($nodes as $node) {
            if (in_array($node['type'] ?? null, self::INLINE, true)) {
                $inline[] = $node;
            } elseif (is_array($node['content'] ?? null)) {
                array_push($inline, ...$this->flatten(array_values($node['content'])));
            }
        }

        return $inline;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function inlineOnly(array $nodes, string $parent): array
    {
        $inline = $this->flatten($nodes);

        if ($parent === 'codeBlock') {
            return array_values(array_filter(
                array_map(fn (array $node): array => ['type' => 'text', 'text' => $this->plainText($node)], $inline),
                fn (array $node): bool => $node['text'] !== '',
            ));
        }

        return $inline;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function plainText(array $node): string
    {
        if ($node['type'] === 'hardBreak') {
            return "\n";
        }

        if ($node['type'] === 'mention' && is_array($node['attrs'] ?? null)) {
            return (is_string($node['attrs']['char'] ?? null) ? $node['attrs']['char'] : '@').(is_string($node['attrs']['label'] ?? null) ? $node['attrs']['label'] : '');
        }

        return is_string($node['text'] ?? null) ? $node['text'] : '';
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function blocks(array $nodes): array
    {
        $blocks = [];
        $run = [];

        foreach ($nodes as $node) {
            if (in_array($node['type'] ?? null, self::INLINE, true)) {
                $run[] = $node;

                continue;
            }

            if ($run !== []) {
                $blocks[] = ['type' => 'paragraph', 'content' => $run];
                $run = [];
            }

            $blocks[] = $node;
        }

        if ($run !== []) {
            $blocks[] = ['type' => 'paragraph', 'content' => $run];
        }

        return $blocks === [] ? [['type' => 'paragraph']] : $blocks;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function listItems(array $nodes): array
    {
        return array_map(
            fn (array $node): array => ($node['type'] ?? null) === 'listItem' ? $node : ['type' => 'listItem', 'content' => $this->blocks([$node])],
            $nodes,
        );
    }
}
