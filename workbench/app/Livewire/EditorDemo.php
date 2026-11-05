<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class EditorDemo extends Component
{
    public string $body = '<p>Hello <strong>world</strong>, ask <span data-type="mention" data-id="7" data-label="Ada Lovelace" data-char="@">@Ada Lovelace</span> or read <a target="_blank" rel="noopener noreferrer nofollow" href="https://example.com/docs">the docs</a>.</p><ul><li><p>First</p></li><li><p>Second</p></li></ul>';

    public string $untrusted = '<p>Safe text</p><img src="x" onerror="window.__xss = true"><a href="javascript:window.__xss = true">bad link</a><script>window.__xss = true</script>';

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

    public function setBodyFromServer(): void
    {
        $this->body = '<h2>From the server</h2><p>Replaced <em>content</em></p>';
    }

    public function render(): View
    {
        return view('workbench::livewire.editor-demo');
    }
}
