<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithConfirmation;

final class ConfirmingComponent extends Component
{
    use WithConfirmation;

    /**
     * @var list<int|string>
     */
    public array $removed = [];

    public function askToRemove(int $id): void
    {
        $this->confirm('remove', [$id], 'Remove it?', 'Gone for good.', 'Remove');
    }

    public function askToDoSomethingElse(string $action): void
    {
        $this->confirm($action, [], 'Sure?', 'Really?', 'Yes');
    }

    protected function remove(int $id): void
    {
        $this->removed[] = $id;
    }

    protected function bootstrapWorkspace(): void
    {
        $this->removed[] = 'bootstrapWorkspace';
    }

    protected function mountVolume(): void
    {
        $this->removed[] = 'mountVolume';
    }

    protected function renderPdf(): void
    {
        $this->removed[] = 'renderPdf';
    }

    protected function exceptional(): void
    {
        $this->removed[] = 'exceptional';
    }

    public function render(): string
    {
        return '<div><x-ui::confirm :confirmation="$confirmation" /></div>';
    }
}
