<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Odden\Ui\Enums\ToastVariant;
use Odden\Ui\Livewire\Concerns\WithConfirmation;
use Odden\Ui\Livewire\Concerns\WithToasts;

#[Layout('workbench::layout')]
final class Demo extends Component
{
    use WithConfirmation;
    use WithToasts;

    public string $name = '';

    public string $plan = 'medium';

    public string $notes = '';

    public bool $notifications = true;

    public bool $showEdit = false;

    public int $deletedId = 0;

    public function save(): void
    {
        $this->validate(['name' => 'required|min:3']);

        $this->showEdit = false;
        $this->toast('Saved');
    }

    public function saveAndLeave(): void
    {
        $this->toastAfterRedirect('Saved for later', ToastVariant::Info);
        $this->redirect('/');
    }

    public function warn(): void
    {
        $this->toast('Careful now', ToastVariant::Warning);
    }

    public function askDelete(int $id): void
    {
        $this->confirm('deleteItem', [$id], 'Delete this item?', 'This cannot be undone.', 'Delete item');
    }

    protected function deleteItem(int $id): void
    {
        $this->deletedId = $id;
        $this->toast("Deleted item {$id}");
    }

    public function render(): View
    {
        return view('workbench::demo');
    }
}
