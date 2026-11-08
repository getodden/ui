<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class FormsDemo extends Component
{
    use WithFileUploads;

    public string $date = '2026-11-07';

    public string $datetime = '2026-11-07T09:30';

    public string $quantity = '3';

    public string $amount = '19.50';

    public bool $agreed = false;

    public string $contactMethod = 'email';

    /**
     * @var list<string>
     */
    public array $tags = ['b'];

    /**
     * @var list<int>
     */
    public array $numericTags = [3, 5];

    public string $customer = '';

    public string $customerName = '';

    public string $owner = '';

    public bool $moreTags = false;

    public bool $moreTabs = false;

    public string $tab = 'details';

    public string $blankTab = '';

    public bool $sectionOpen = false;

    public string $lastAction = '';

    public ?TemporaryUploadedFile $attachment = null;

    /**
     * @return list<array{id: string|int, label: string}>
     */
    #[Renderless]
    public function searchCustomers(string $query): array
    {
        $customers = [
            ['id' => 1, 'label' => 'Grace Hopper'],
            ['id' => 2, 'label' => 'Grace Kelly'],
            ['id' => 3, 'label' => 'Alan Turing'],
            ['id' => 4, 'label' => 'Ada Lovelace'],
        ];

        if ($query === 'slow') {
            usleep(400000);
        }

        return array_values(array_filter(
            $customers,
            fn (array $customer): bool => $query === '' || stripos($customer['label'], mb_substr($query, 0, 50)) !== false,
        ));
    }

    public function updatedCustomer(string $value): void
    {
        $this->customerName = collect($this->searchCustomers(''))->firstWhere('id', (int) $value)['label'] ?? '';
    }

    public function markAction(string $action): void
    {
        $this->lastAction = $action;
    }

    public function validateForm(): void
    {
        $this->validate([
            'date' => 'required|date|after:2030-01-01',
            'quantity' => 'required|integer|min:10',
            'amount' => 'required|numeric|min:100',
            'agreed' => 'accepted',
            'contactMethod' => 'in:phone',
            'tags' => 'array|min:2',
            'customer' => 'required',
            'attachment' => 'required',
        ]);
    }

    public function render(): View
    {
        return view('workbench::livewire.forms-demo');
    }
}
