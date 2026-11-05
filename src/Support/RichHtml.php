<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

use Dom\Element as HtmlElement;
use Dom\HTMLDocument;
use Dom\Text as HtmlText;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use LengthException;

final class RichHtml
{
    public const MAXIMUM_BYTES = 262144;

    public const MAXIMUM_TAGS = 10000;

    private const MAXIMUM_DEPTH = 100;

    private const MAXIMUM_PASSES = 5;

    private const HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';

    private const ORDERED_LIST_TYPES = ['1', 'a', 'A', 'i', 'I'];

    private const CODE_LANGUAGE_CLASS = '/^language-[A-Za-z0-9_+-]{1,32}$/';

    private const DROPPED_WITH_CONTENT = [
        'script', 'style', 'template', 'iframe', 'object', 'embed', 'svg', 'math',
        'noscript', 'textarea', 'title', 'head', 'frameset', 'noframes', 'noembed', 'xmp', 'plaintext',
    ];

    private const RENAMED = [
        'b' => 'strong',
        'i' => 'em',
        'strike' => 's',
        'del' => 's',
    ];

    private const PLAIN = [
        'p', 'strong', 'em', 'u', 's', 'pre', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'ul', 'li',
    ];

    public static function clean(string $html): string
    {
        return self::stabilise($html, false);
    }

    /**
     * @internal
     */
    public static function cleanWithLegacyParser(string $html): string
    {
        return self::stabilise($html, true);
    }

    private static function stabilise(string $html, bool $legacyParser): string
    {
        if (strlen($html) > self::MAXIMUM_BYTES || substr_count($html, '<') > self::MAXIMUM_TAGS) {
            throw new LengthException('The HTML is larger than RichHtml::clean() accepts.');
        }

        $current = self::pass($html, $legacyParser);

        for ($pass = 1; $pass < self::MAXIMUM_PASSES; $pass++) {
            $next = self::pass($current, $legacyParser);

            if ($next === $current) {
                break;
            }

            $current = $next;
        }

        return $current;
    }

    private static function pass(string $html, bool $legacyParser): string
    {
        $html = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', mb_scrub($html, 'UTF-8'));

        if (trim($html) === '') {
            return '';
        }

        $body = $legacyParser || ! class_exists(HTMLDocument::class)
            ? self::parseWithLegacyParser($html)
            : self::parseWithHtml5Parser($html);

        if ($body === null) {
            return '';
        }

        return trim(self::children($body, 0));
    }

    private static function parseWithHtml5Parser(string $html): ?HtmlElement
    {
        $document = HTMLDocument::createFromString(
            '<!DOCTYPE html><html><head></head><body>'.$html.'</body></html>',
            LIBXML_NOERROR,
            'UTF-8',
        );

        return $document->body;
    }

    private static function parseWithLegacyParser(string $html): ?DOMElement
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="utf-8"?><html><body>'.$html.'</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        return $body instanceof DOMElement ? $body : null;
    }

    private static function children(DOMNode|HtmlElement $parent, int $depth): string
    {
        $output = '';

        foreach ($parent->childNodes as $child) {
            $output .= self::node($child, $depth);
        }

        return $output;
    }

    private static function node(mixed $node, int $depth): string
    {
        if ($node instanceof DOMElement || $node instanceof HtmlElement) {
            return self::element($node, $depth);
        }

        if ($node instanceof DOMText || $node instanceof HtmlText) {
            return self::escapeText($node->data);
        }

        return '';
    }

    private static function element(DOMElement|HtmlElement $element, int $depth): string
    {
        if ($depth >= self::MAXIMUM_DEPTH) {
            return '';
        }

        $namespace = $element->namespaceURI;

        if ($namespace !== null && $namespace !== self::HTML_NAMESPACE) {
            return in_array(strtolower((string) $element->localName), self::DROPPED_WITH_CONTENT, true) ? '' : self::unwrap($element, $depth);
        }

        $name = strtolower((string) $element->localName);

        if (in_array($name, self::DROPPED_WITH_CONTENT, true)) {
            return '';
        }

        $name = self::RENAMED[$name] ?? $name;

        return match (true) {
            $name === 'br' || $name === 'hr' => "<{$name}>",
            $name === 'code' => self::code($element, $depth),
            $name === 'ol' => self::orderedList($element, $depth),
            $name === 'a' => self::anchor($element, $depth),
            $name === 'span' => self::mention($element, $depth),
            in_array($name, self::PLAIN, true) => self::plain($name, $element, $depth),
            default => self::unwrap($element, $depth),
        };
    }

    private static function unwrap(DOMElement|HtmlElement $element, int $depth): string
    {
        return self::children($element, $depth + 1);
    }

    private static function plain(string $name, DOMElement|HtmlElement $element, int $depth): string
    {
        return "<{$name}>".self::children($element, $depth + 1)."</{$name}>";
    }

    private static function code(DOMElement|HtmlElement $element, int $depth): string
    {
        $parent = $element->parentNode;
        $insidePre = ($parent instanceof DOMElement || $parent instanceof HtmlElement) && strtolower((string) $parent->localName) === 'pre';
        $class = '';

        if ($insidePre) {
            foreach (preg_split('/\s+/', trim((string) $element->getAttribute('class'))) ?: [] as $token) {
                if (preg_match(self::CODE_LANGUAGE_CLASS, $token) === 1) {
                    $class = ' class="'.$token.'"';

                    break;
                }
            }
        }

        return "<code{$class}>".self::children($element, $depth + 1).'</code>';
    }

    private static function orderedList(DOMElement|HtmlElement $element, int $depth): string
    {
        $start = trim((string) $element->getAttribute('start'));
        $type = trim((string) $element->getAttribute('type'));
        $attribute = preg_match('/^\d{1,9}$/', $start) === 1 && $start !== '1' ? ' start="'.(int) $start.'"' : '';
        $attribute .= in_array($type, self::ORDERED_LIST_TYPES, true) ? ' type="'.$type.'"' : '';

        return "<ol{$attribute}>".self::children($element, $depth + 1).'</ol>';
    }

    private static function anchor(DOMElement|HtmlElement $element, int $depth): string
    {
        $href = SafeUri::clean((string) $element->getAttribute('href'));
        $inner = self::children($element, $depth + 1);

        if ($href === null) {
            return $inner;
        }

        $target = strtolower(trim((string) $element->getAttribute('target'))) === '_blank' ? ' target="_blank"' : '';

        return '<a'.$target.' rel="noopener noreferrer nofollow" href="'.self::escapeAttribute($href).'">'.$inner.'</a>';
    }

    private static function mention(DOMElement|HtmlElement $element, int $depth): string
    {
        if ($element->getAttribute('data-type') !== 'mention') {
            return self::unwrap($element, $depth);
        }

        $attributes = '';

        foreach (['data-id', 'data-label', 'data-char'] as $name) {
            if ($element->hasAttribute($name)) {
                $attributes .= ' '.$name.'="'.self::escapeAttribute((string) $element->getAttribute($name)).'"';
            }
        }

        return '<span data-type="mention"'.$attributes.'>'.self::escapeText((string) $element->textContent).'</span>';
    }

    private static function escapeText(string $text): string
    {
        return str_replace("\u{00A0}", '&nbsp;', htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'));
    }

    private static function escapeAttribute(string $value): string
    {
        return str_replace("\u{00A0}", '&nbsp;', htmlspecialchars($value, ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'));
    }
}
