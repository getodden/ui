<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithBoard;
use Workbench\App\Models\Fruit;

final class BoardDemo extends Component
{
    use WithBoard;

    #[Url]
    public bool $manual = false;

    #[Url]
    public bool $viewer = false;

    /**
     * @var array<string, mixed>
     */
    public array $lastMove = [];

    private const array COLUMNS = ['red' => 'Red', 'green' => 'Green', 'yellow' => 'Yellow', 'purple' => 'Purple (locked)'];

    protected function boardColumnKeys(): array
    {
        return array_keys(self::COLUMNS);
    }

    protected function boardPageSize(): int
    {
        return 3;
    }

    protected function moveBoardCard(string $cardId, string $toColumn, int $position, ?string $afterCardId): bool
    {
        $this->lastMove = ['card' => $cardId, 'column' => $toColumn, 'position' => $position, 'after' => $afterCardId];

        if ($toColumn === 'purple') {
            return false;
        }

        $fruit = Fruit::query()->whereKey($cardId)->whereIn('colour', array_keys(self::COLUMNS))->first();

        if ($fruit === null) {
            return false;
        }

        $fruit->update(['colour' => $toColumn]);

        return true;
    }

    public function render(): View
    {
        $columns = [];

        foreach (self::COLUMNS as $key => $label) {
            $query = Fruit::query()->where('colour', $key)->orderBy('name');

            $columns[$key] = [
                'label' => $label,
                'count' => Fruit::query()->where('colour', $key)->count(),
                'total' => '$'.number_format(Fruit::query()->where('colour', $key)->sum('price') / 100, 2),
                'slice' => $this->boardSlice($query, $key),
            ];
        }

        return view('workbench::livewire.board-demo', ['columns' => $columns, 'labels' => self::COLUMNS]);
    }
}
