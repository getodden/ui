<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class EditorDemo extends Component
{
    public string $body = '<p>Hello <strong>world</strong>, ask <span data-type="mention" data-id="7" data-label="Ada Lovelace" data-char="@">@Ada Lovelace</span> or read <a href="https://example.com/docs">the docs</a>.</p><ul><li><p>First</p></li><li><p>Second</p></li></ul>';

    public string $untrusted = '<p>Safe text</p><img src="x" onerror="window.__xss = true"><a href="javascript:window.__xss = true">bad link</a><script>window.__xss = true</script>';

    public string $filament = '<h1>One</h1><h2>Two</h2><h3>Three</h3><p>Plain <strong>bold</strong> <em>italic</em> <u>under</u> <s>strike</s> <code>code</code> <a href="https://example.com/a?b=1&amp;c=2">abs</a> <a href="/relative/path">rel</a> <span data-type="mention" data-id="7" data-label="Ada Lovelace" data-char="@">@Ada Lovelace</span>&nbsp;x<br>after</p><ul><li><p>a</p></li></ul><ol start="3" type="a"><li><p>c</p></li></ol><blockquote><p>q</p></blockquote><pre><code class="language-php">echo 1;</code></pre><hr><p>end</p>';

    public string $debounced = '';

    public string $blurBody = '';

    public string $liveBlurBody = '';

    public string $oddBody = '';

    public string $peopleBody = '';

    public string $searchBody = '';

    public string $emptyBody = '';

    /**
     * @var array<string, mixed>
     */
    public array $mentionDoc = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Hi '],
                    ['type' => 'mention', 'attrs' => ['id' => '7', 'label' => 'Ada Lovelace', 'char' => '@']],
                    ['type' => 'text', 'text' => ' there'],
                ],
            ],
        ],
    ];

    /**
     * @var array<string, mixed>
     */
    public array $unreadableDoc = [
        'type' => 'doc',
        'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Before']]],
            ['type' => 'image', 'attrs' => ['src' => 'https://example.com/x.png']],
        ],
    ];

    /**
     * @var array<string, mixed>
     */
    public array $emptyDoc = [];

    /**
     * @var array<string, mixed>
     */
    public array $linkDoc = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'bad', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:window.__xss = true', 'target' => '_blank']]]],
                    ['type' => 'text', 'text' => ' good', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://example.com', 'target' => '_blank']]]],
                ],
            ],
        ],
    ];

    /**
     * @return list<array{id: string|int, label: string}>
     */
    public function searchPeople(string $query): array
    {
        $people = [
            ['id' => 1, 'label' => 'Grace Hopper'],
            ['id' => '2', 'label' => 'Grace Kelly'],
            ['id' => 3, 'label' => 'Alan Turing'],
        ];

        return array_values(array_filter(
            $people,
            fn (array $person): bool => $query === '' || stripos($person['label'], $query) !== false,
        ));
    }

    public function setMentionDocToImage(): void
    {
        $this->mentionDoc = $this->unreadableDoc;
    }

    public function setEmptyDocToImage(): void
    {
        $this->emptyDoc = $this->unreadableDoc;
    }

    public function padBody(): void
    {
        $this->body .= "\n";
    }

    public function setBodyFromServer(): void
    {
        $this->body = '<h2>From the server</h2><p>Replaced <em>content</em></p>';
    }

    public function render(): View
    {
        return view('workbench::livewire.editor-demo');
    }
}
