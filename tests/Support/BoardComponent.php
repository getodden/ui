<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Odden\Ui\Livewire\Concerns\WithBoard;
use Workbench\App\Models\Fruit;

class BoardComponent extends Component
{
    use WithBoard;

    /**
     * @var list<array{card: string, column: string, position: int}>
     */
    public array $moves = [];

    public string $outcome = 'accept';

    public int $pageSize = 2;

    protected function boardColumnKeys(): array
    {
        return ['red', 'green', 'yellow'];
    }

    protected function boardPageSize(): int
    {
        return $this->pageSize;
    }

    protected function moveBoardCard(string $cardId, string $toColumn, int $position): bool
    {
        $this->moves[] = ['card' => $cardId, 'column' => $toColumn, 'position' => $position];

        if ($this->outcome === 'forbid') {
            throw new AuthorizationException;
        }

        return $this->outcome === 'accept';
    }

    public function limitOf(string $column): int
    {
        return $this->boardLimit($column);
    }

    public function rowsAfterSlicing(): int
    {
        $query = Fruit::query()->where('colour', 'red');

        $this->boardSlice($query, 'red');

        return $query->get()->count();
    }

    public function render(): string
    {
        $columns = [];

        foreach (['red' => 'Red', 'green' => 'Green', 'yellow' => 'Yellow'] as $key => $label) {
            $query = Fruit::query()->where('colour', $key)->orderBy('name');
            $columns[$key] = [$label, $query->count(), $this->boardSlice($query, $key)];
        }

        return Blade::render(<<<'BLADE'
        <div>
            <x-ui::board label="Fruit" :columns="['red' => 'Red', 'green' => 'Green', 'yellow' => 'Yellow']">
                @foreach ($columns as $key => [$label, $count, $slice])
                    <x-ui::board.column :column="$key" :label="$label" :count="$count" total="{{ $count }} items" :has-more="$slice->hasMore">
                        @foreach ($slice->records as $fruit)
                            <x-ui::board.card :id="$fruit->id" :label="$fruit->name">{{ $fruit->name }}</x-ui::board.card>
                        @endforeach
                    </x-ui::board.column>
                @endforeach
            </x-ui::board>
        </div>
        BLADE, ['columns' => $columns]);
    }
}
