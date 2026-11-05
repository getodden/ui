<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Odden\Ui\Support\RichHtml;
use Workbench\App\Livewire\EditorDemo;

dataset('parsers', ['html5' => [false], 'legacy' => [true]]);

function cleanWith(bool $legacy, string $html): string
{
    return $legacy ? RichHtml::cleanWithLegacyParser($html) : RichHtml::clean($html);
}

dataset('payloads', [
    'script tag' => '<script>alert(1)</script>',
    'uppercase script' => '<SCRIPT SRC=//evil.example/x.js></SCRIPT>',
    'script with attributes' => '<script type="text/javascript">alert(1)</script>',
    'image onerror' => '<img src=x onerror=alert(1)>',
    'image onerror quoted' => '<IMG SRC="x" ONERROR="alert(1)">',
    'svg onload' => '<svg onload=alert(1)></svg>',
    'svg script' => '<svg><script>alert(1)</script></svg>',
    'svg in a paragraph' => '<p>a<svg><a xlink:href="javascript:alert(1)"><text>x</text></a></svg>b</p>',
    'math' => '<math><mtext><script>alert(1)</script></mtext></math>',
    'iframe' => '<iframe src="javascript:alert(1)"></iframe>',
    'iframe srcdoc' => '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
    'object' => '<object data="javascript:alert(1)"></object>',
    'embed' => '<embed src="javascript:alert(1)">',
    'link tag' => '<link rel="stylesheet" href="javascript:alert(1)">',
    'meta refresh' => '<meta http-equiv="refresh" content="0;url=javascript:alert(1)">',
    'base' => '<base href="javascript:alert(1)//">',
    'style' => '<style>@import "javascript:alert(1)";</style>',
    'style attribute' => '<p style="background:url(javascript:alert(1))">x</p>',
    'handler on paragraph' => '<p onclick="alert(1)" onmouseover=alert(1)>x</p>',
    'handler on link' => '<a href="https://example.com" onclick="alert(1)">x</a>',
    'javascript href' => '<a href="javascript:alert(1)">x</a>',
    'javascript uppercase' => '<a href="JaVaScRiPt:alert(1)">x</a>',
    'javascript with tab' => '<a href="jav&#x09;ascript:alert(1)">x</a>',
    'javascript with newline' => '<a href="jav&#x0A;ascript:alert(1)">x</a>',
    'javascript with carriage return' => '<a href="jav&#13;ascript:alert(1)">x</a>',
    'javascript with entities' => '<a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(1)">x</a>',
    'javascript with hex entities' => '<a href="&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A;alert(1)">x</a>',
    'javascript with colon entity' => '<a href="javascript&colon;alert(1)">x</a>',
    'javascript with leading space' => '<a href="   javascript:alert(1)">x</a>',
    'javascript with leading control' => "<a href=\"\x01javascript:alert(1)\">x</a>",
    'javascript with zero width' => "<a href=\"java\u{200B}script:alert(1)\">x</a>",
    'javascript with nul' => "<a href=\"java\x00script:alert(1)\">x</a>",
    'data html' => '<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">x</a>',
    'data uppercase' => '<a href="DATA:text/html,<script>alert(1)</script>">x</a>',
    'vbscript' => '<a href="vbscript:msgbox(1)">x</a>',
    'vbscript entities' => '<a href="vb&#x73;cript:msgbox(1)">x</a>',
    'file scheme' => '<a href="file:///etc/passwd">x</a>',
    'protocol relative' => '<a href="//evil.example/x">x</a>',
    'relative' => '<a href="/admin/delete">x</a>',
    'unquoted href' => '<a href=javascript:alert(1)>x</a>',
    'backtick attribute' => '<a href=`javascript:alert(1)`>x</a>',
    'form action' => '<form action="javascript:alert(1)"><button formaction="javascript:alert(1)">x</button></form>',
    'input autofocus' => '<input autofocus onfocus=alert(1)>',
    'details toggle' => '<details open ontoggle=alert(1)>x</details>',
    'video source' => '<video><source onerror=alert(1)></video>',
    'body onload' => '<body onload=alert(1)>x',
    'nested unclosed' => '<p><strong><em><a href="javascript:alert(1)">x',
    'comment' => '<!-- <script>alert(1)</script> --><p>x</p>',
    'comment breakout' => '<!--><script>alert(1)</script>-->',
    'conditional comment' => '<!--[if IE]><script>alert(1)</script><![endif]-->',
    'cdata' => '<![CDATA[<script>alert(1)</script>]]>',
    'xml processing instruction' => '<?xml version="1.0"?><script>alert(1)</script>',
    'noscript mxss' => '<noscript><p title="</noscript><img src=x onerror=alert(1)>">',
    'noscript mxss unterminated' => '<noscript><style></noscript><img src=x onerror=alert(1)>',
    'style mxss' => '<style><style/><img src=x onerror=alert(1)>',
    'textarea mxss' => '<textarea><p title="</textarea><img src=x onerror=alert(1)>">',
    'title mxss' => '<title><p title="</title><img src=x onerror=alert(1)>">',
    'template' => '<template><script>alert(1)</script></template>',
    'xmp' => '<xmp><p title="</xmp><img src=x onerror=alert(1)>"></xmp>',
    'plaintext' => '<plaintext><script>alert(1)</script>',
    'table mxss' => '<table><td><a href="javascript:alert(1)">x</a></td></table>',
    'select mxss' => '<select><template><script>alert(1)</script></template></select>',
    'malformed tag' => '<scr<script>ipt>alert(1)</scr</script>ipt>',
    'tag with slash' => '<img/src=x/onerror=alert(1)>',
    'null in tag' => "<scr\x00ipt>alert(1)</scr\x00ipt>",
    'broken utf8' => "<p>\xC3\x28</p><script>alert(1)</script><a href=\"jav\xFFascript:alert(1)\">x</a>",
    'mention with handler' => '<span data-type="mention" data-id="1" data-label="x" onclick="alert(1)" style="x">@x</span>',
    'mention with script child' => '<span data-type="mention" data-id="1" data-label="x"><script>alert(1)</script>@x</span>',
    'mention label breakout' => '<span data-type="mention" data-id="1" data-label="&quot; onmouseover=&quot;alert(1)" data-char="@">@x</span>',
    'mention data attributes' => '<span data-type="mention" data-id="1" data-label="x" data-evil="y" data-type2="z">@x</span>',
    'class and id' => '<p class="a" id="b" data-x="c" aria-label="d">x</p>',
    'target blank without rel' => '<a href="https://example.com" target="_blank">x</a>',
    'target javascript' => '<a href="https://example.com" target="javascript:alert(1)">x</a>',
    'link in heading' => '<h2><a href="javascript:alert(1)">x</a></h2>',
    'deeply nested' => str_repeat('<div>', 500).'<script>alert(1)</script>x'.str_repeat('</div>', 500),
    'deeply nested lists' => str_repeat('<ul><li>', 300).'<img src=x onerror=alert(1)>',
]);

function assertSafe(string $clean): void
{
    $allowed = [
        'a' => ['target', 'rel', 'href'],
        'span' => ['data-type', 'data-id', 'data-label', 'data-char'],
        'ol' => ['start'],
        'p' => [], 'br' => [], 'strong' => [], 'em' => [], 'u' => [], 's' => [], 'code' => [], 'pre' => [],
        'blockquote' => [], 'hr' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'li' => [],
    ];

    $document = HTMLDocument::createFromString('<!DOCTYPE html><body>'.$clean.'</body>', LIBXML_NOERROR);

    foreach ($document->body->querySelectorAll('*') as $element) {
        $name = strtolower($element->localName);

        expect($allowed)->toHaveKey($name);

        foreach ($element->attributes as $attribute) {
            expect($allowed[$name])->toContain($attribute->name);
        }

        if ($name === 'a') {
            expect($element->getAttribute('rel'))->toBe('noopener noreferrer nofollow')
                ->and($element->getAttribute('href'))->toMatch('/^((https?|mailto|tel):|\/(?![\/\\\\])|#|\?)/i');

            if ($element->hasAttribute('target')) {
                expect($element->getAttribute('target'))->toBe('_blank');
            }
        }
    }

    expect($clean)->not->toMatch('/<script/i');
}

it('leaves nothing executable from an attack payload', function (string $payload, bool $legacy): void {
    assertSafe(cleanWith($legacy, $payload));
})->with('payloads', 'parsers');

it('settles on a fixed point for every payload', function (string $payload, bool $legacy): void {
    $clean = cleanWith($legacy, $payload);

    expect(cleanWith($legacy, $clean))->toBe($clean);
})->with('payloads', 'parsers');

it('keeps the text around dropped markup but not the text of script-like elements', function (bool $legacy): void {
    expect(cleanWith($legacy, '<div>keep <script>alert(1)</script>this <b>bold</b> <style>p{}</style>text</div>'))->toBe('keep this <strong>bold</strong> text')
        ->and(cleanWith($legacy, '<table><tr><td>cell</td></tr></table>'))->toBe('cell');
})->with('parsers');

it('drops the contents of dangerous containers', function (string $html, bool $legacy): void {
    expect(cleanWith($legacy, "<p>a</p>{$html}<p>b</p>"))->toBe('<p>a</p><p>b</p>');
})->with([
    'svg' => '<svg><text>secret</text></svg>',
    'math' => '<math><mi>secret</mi></math>',
    'iframe' => '<iframe>secret</iframe>',
    'object' => '<object>secret</object>',
    'template' => '<template>secret</template>',
    'noscript' => '<noscript>secret</noscript>',
    'textarea' => '<textarea>secret</textarea>',
    'title' => '<title>secret</title>',
    'style' => '<style>secret</style>',
])->with('parsers');

it('keeps Filament-shaped content unchanged', function (string $html, bool $legacy): void {
    expect(cleanWith($legacy, $html))->toBe($html);
})->with([
    'paragraph with marks' => '<p>Plain <strong>bold</strong> <em>italic</em> <u>under</u> <s>struck</s> <code>code</code></p>',
    'mention' => '<p>Hi <span data-type="mention" data-id="7" data-label="Ada Lovelace" data-char="@">@Ada Lovelace</span> there</p>',
    'link' => '<p>Read <a target="_blank" rel="noopener noreferrer nofollow" href="https://example.com/docs?a=1&amp;b=2">the docs</a></p>',
    'mailto and tel' => '<p><a rel="noopener noreferrer nofollow" href="mailto:ada@example.com">mail</a> <a rel="noopener noreferrer nofollow" href="tel:+15550100">call</a></p>',
    'headings' => '<h2>Two</h2><h3>Three</h3><h4>Four</h4>',
    'bullet list' => '<ul><li><p>One</p></li><li><p>Two</p><ul><li><p>Nested</p></li></ul></li></ul>',
    'ordered list with start' => '<ol start="3"><li><p>Three</p></li><li><p>Four</p></li></ol>',
    'ordered list' => '<ol><li><p>One</p></li></ol>',
    'blockquote' => '<blockquote><p>Quoted</p></blockquote>',
    'code block' => '<pre><code>line 1'."\n".'line 2</code></pre>',
    'horizontal rule' => '<p>a</p><hr><p>b</p>',
    'hard break' => '<p>one<br>two</p>',
    'escaped text' => '<p>1 &lt; 2 &amp; 3 &gt; 2 and "quotes" and \'single\'</p>',
    'unicode' => '<p>Zoë — 日本語 😀</p>',
])->with('parsers');

it('writes the canonical form of equivalent markup', function (string $html, string $expected, bool $legacy): void {
    expect(cleanWith($legacy, $html))->toBe($expected);
})->with([
    'b i strike' => ['<p><b>a</b><i>b</i><strike>c</strike></p>', '<p><strong>a</strong><em>b</em><s>c</s></p>'],
    'uppercase tags' => ['<P><STRONG>a</STRONG></P>', '<p><strong>a</strong></p>'],
    'link without target gets rel' => ['<a href="https://example.com">x</a>', '<a rel="noopener noreferrer nofollow" href="https://example.com">x</a>'],
    'link target other than blank is dropped' => ['<a href="https://example.com" target="_top" rel="opener">x</a>', '<a rel="noopener noreferrer nofollow" href="https://example.com">x</a>'],
    'uppercase scheme' => ['<a href="HTTPS://Example.com/A">x</a>', '<a rel="noopener noreferrer nofollow" href="HTTPS://Example.com/A">x</a>'],
    'padded href' => ['<a href="  https://example.com  ">x</a>', '<a rel="noopener noreferrer nofollow" href="https://example.com">x</a>'],
    'unsafe link keeps its text' => ['<p><a href="javascript:alert(1)">click</a></p>', '<p>click</p>'],
    'relative link is kept' => ['<a href="/x">click</a>', '<a rel="noopener noreferrer nofollow" href="/x">click</a>'],
    'bare relative link keeps its text' => ['<a href="x">click</a>', 'click'],
    'ordered list start one' => ['<ol start="1"><li>a</li></ol>', '<ol><li>a</li></ol>'],
    'ordered list bad start' => ['<ol start="x"><li>a</li></ol>', '<ol><li>a</li></ol>'],
    'other span' => ['<p><span class="x">a</span></p>', '<p>a</p>'],
    'mention keeps only its data attributes' => ['<span data-type="mention" class="x" data-id="1" data-label="A" data-char="#" title="t">#A</span>', '<span data-type="mention" data-id="1" data-label="A" data-char="#">#A</span>'],
    'mention label is escaped' => ['<span data-type="mention" data-id="1" data-label="&quot; x=&quot;">@x</span>', '<span data-type="mention" data-id="1" data-label="&quot; x=&quot;">@x</span>'],
    'empty' => ['', ''],
    'whitespace only' => ["  \n ", ''],
    'comments only' => ['<!-- x -->', ''],
    'script only' => ['<script>x</script>', ''],
])->with('parsers');

it('replaces invalid UTF-8 instead of failing', function (bool $legacy): void {
    $clean = cleanWith($legacy, "<p>ok \xC3\x28 end</p>");

    expect(mb_check_encoding($clean, 'UTF-8'))->toBeTrue()
        ->and($clean)->toStartWith('<p>ok ')
        ->and($clean)->toEndWith(' end</p>');
})->with('parsers');

it('agrees between the HTML5 parser and the legacy parser on editor output', function (string $html): void {
    expect(RichHtml::clean($html))->toBe(RichHtml::cleanWithLegacyParser($html));
})->with([
    '<p>Hello <strong>world</strong></p><ul><li><p>a</p></li></ul>',
    '<p>x <span data-type="mention" data-id="7" data-label="Ada" data-char="@">@Ada</span></p>',
    '<h2>T</h2><p><a target="_blank" rel="noopener noreferrer nofollow" href="https://example.com">l</a></p>',
]);

it('stays stable on random tag soup', function (): void {
    mt_srand(20260515);

    $pieces = ['<p>', '</p>', '<b>', '</b>', '<a href="https://example.com">', '<a href="javascript:x">', '</a>', '<ul>', '<li>', '</li>', '</ul>', '<ol start="2">', '</ol>', '<pre>', '</pre>', '<h2>', '</h2>', '<br>', '<hr>', '<span data-type="mention" data-id="1" data-label="A">', '</span>', '<script>', '</script>', '<svg>', '</svg>', '<noscript>', '</noscript>', '<table>', '<td>', '<div>', '</div>', 'text', ' ', "\n", '&amp;', '<', '>', '"', '<!--', '-->', '<img src=x onerror=x>', '<blockquote>', '</blockquote>', '<code>', '</code>'];

    for ($round = 0; $round < 300; $round++) {
        $html = '';

        for ($piece = 0; $piece < mt_rand(1, 25); $piece++) {
            $html .= $pieces[mt_rand(0, count($pieces) - 1)];
        }

        $clean = RichHtml::clean($html);

        assertSafe($clean);
        expect(RichHtml::clean($clean))->toBe($clean);
    }
});

it('refuses input over the byte limit and over the tag limit', function (): void {
    expect(fn () => RichHtml::clean(str_repeat('a', RichHtml::MAXIMUM_BYTES + 1)))->toThrow(LengthException::class)
        ->and(fn () => RichHtml::clean(str_repeat('<b>', RichHtml::MAXIMUM_TAGS + 1)))->toThrow(LengthException::class)
        ->and(fn () => RichHtml::cleanWithLegacyParser(str_repeat('<b>', RichHtml::MAXIMUM_TAGS + 1)))->toThrow(LengthException::class)
        ->and(RichHtml::clean(str_repeat('a', RichHtml::MAXIMUM_BYTES)))->toBeString();
});

it('cleans input at the tag limit quickly', function (): void {
    $started = microtime(true);

    $clean = RichHtml::clean(str_repeat('<div>', RichHtml::MAXIMUM_TAGS).'deep');

    expect(microtime(true) - $started)->toBeLessThan(1.0)
        ->and($clean)->toBeString();
});

it('keeps relative hrefs that stay on the site and refuses protocol-relative ones', function (string $href, ?string $kept, bool $legacy): void {
    $expected = $kept === null ? 'x' : '<a rel="noopener noreferrer nofollow" href="'.$kept.'">x</a>';

    expect(cleanWith($legacy, '<a href="'.$href.'">x</a>'))->toBe($expected);
})->with([
    'path' => ['/docs/a?b=1', '/docs/a?b=1'],
    'fragment' => ['#section', '#section'],
    'query' => ['?page=2', '?page=2'],
    'protocol relative' => ['//evil.example/x', null],
    'backslash host' => ['/\\evil.example', null],
    'tab host' => ['/&#9;/evil.example', null],
    'bare word' => ['docs/a', null],
])->with('parsers');

it('keeps what Filament stores that the editor can show', function (string $html, bool $legacy): void {
    expect(cleanWith($legacy, $html))->toBe($html);
})->with([
    'h1' => '<h1>One</h1>',
    'code language' => '<pre><code class="language-php">echo 1;</code></pre>',
    'ordered list type' => '<ol start="3" type="a"><li><p>c</p></li></ol>',
    'ordered list type only' => '<ol type="I"><li><p>c</p></li></ol>',
    'non-breaking space' => '<p>a&nbsp;b</p>',
])->with('parsers');

it('drops what does not belong in a code element or an ordered list', function (string $html, string $expected, bool $legacy): void {
    expect(cleanWith($legacy, $html))->toBe($expected);
})->with([
    'language class outside pre' => ['<p><code class="language-php">x</code></p>', '<p><code>x</code></p>'],
    'other classes' => ['<pre><code class="evil language-js hljs">x</code></pre>', '<pre><code class="language-js">x</code></pre>'],
    'bad language' => ['<pre><code class="language-a&quot;b">x</code></pre>', '<pre><code>x</code></pre>'],
    'bad list type' => ['<ol type="z"><li>a</li></ol>', '<ol><li>a</li></ol>'],
    'del' => ['<p><del>gone</del></p>', '<p><s>gone</s></p>'],
])->with('parsers');

it('leaves Filament-shaped HTML unchanged', function (bool $legacy): void {
    $html = (new EditorDemo)->filament;

    expect(cleanWith($legacy, $html))->toBe($html);
})->with('parsers');
