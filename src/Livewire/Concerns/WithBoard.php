<?php

declare(strict_types=1);

namespace Odden\Ui\Livewire\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Livewire\Attributes\Locked;
use Odden\Ui\Support\BoardSlice;

trait WithBoard
{
    private const int MAXIMUM_POSITION = 100000;

    private const int MAXIMUM_LIMIT = 1000;

    /**
     * @var array<string, int>
     */
    #[Locked]
    public array $boardLimits = [];

    /**
     * @return list<string>
     */
    abstract protected function boardColumnKeys(): array;

    abstract protected function moveBoardCard(string $cardId, string $toColumn, int $position): bool;

    protected function boardPageSize(): int
    {
        return 20;
    }

    public function moveCard(mixed $card, mixed $column, mixed $position): bool
    {
        $cardId = $this->boardCardId($card);
        $toColumn = $this->boardColumnKey($column);

        $toPosition = $this->boardPosition($position);

        if ($cardId === null || $toColumn === null || $toPosition === null) {
            return false;
        }

        try {
            return $this->moveBoardCard($cardId, $toColumn, $toPosition);
        } catch (AuthorizationException) {
            return false;
        }
    }

    public function loadMoreCards(mixed $column): void
    {
        $key = $this->boardColumnKey($column);

        if ($key === null) {
            return;
        }

        $this->boardLimits[$key] = min($this->boardLimit($key) + $this->boardPageSize(), self::MAXIMUM_LIMIT);
    }

    protected function boardLimit(string $column): int
    {
        return min(max(1, $this->boardLimits[$column] ?? $this->boardPageSize()), self::MAXIMUM_LIMIT);
    }

    /**
     * @param  EloquentBuilder<*>|QueryBuilder  $query
     */
    protected function boardSlice(EloquentBuilder|QueryBuilder $query, string $column): BoardSlice
    {
        $limit = $this->boardLimit($column);
        $records = (clone $query)->limit($limit + 1)->get();

        return new BoardSlice($records->take($limit)->values(), $records->count() > $limit);
    }

    private function boardCardId(mixed $card): ?string
    {
        $id = is_int($card) ? (string) $card : $card;

        if (! is_string($id) || $id === '' || mb_strlen($id) > 100 || ! mb_check_encoding($id, 'UTF-8')) {
            return null;
        }

        return $id;
    }

    private function boardPosition(mixed $position): ?int
    {
        if (is_string($position) && ctype_digit($position) && strlen($position) <= 9) {
            $position = (int) $position;
        }

        return is_int($position) ? min(max($position, 0), self::MAXIMUM_POSITION) : null;
    }

    private function boardColumnKey(mixed $column): ?string
    {
        $key = is_int($column) ? (string) $column : $column;

        if (! is_string($key)) {
            return null;
        }

        foreach ($this->boardColumnKeys() as $allowed) {
            if ((string) $allowed === $key) {
                return (string) $allowed;
            }
        }

        return null;
    }
}
