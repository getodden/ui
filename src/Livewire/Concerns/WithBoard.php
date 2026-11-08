<?php

declare(strict_types=1);

namespace Odden\Ui\Livewire\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Odden\Ui\Support\BoardSlice;
use stdClass;

trait WithBoard
{
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

    abstract protected function moveBoardCard(string $cardId, string $toColumn, int $position, ?string $afterCardId): bool;

    protected function boardPageSize(): int
    {
        return 20;
    }

    public function moveCard(mixed $card, mixed $column, mixed $position, mixed $after = null): bool
    {
        $cardId = $this->boardCardId($card);
        $toColumn = $this->boardColumnKey($column);

        $toPosition = $this->boardPosition($position);

        $afterId = $after === null ? null : $this->boardCardId($after);

        if ($cardId === null || $toColumn === null || $toPosition === null || ($after !== null && $afterId === null)) {
            return false;
        }

        try {
            return $this->moveBoardCard($cardId, $toColumn, $toPosition, $afterId);
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
     * @template TRecord of Model
     *
     * @param  EloquentBuilder<TRecord>|QueryBuilder  $query
     * @return ($query is EloquentBuilder<TRecord> ? BoardSlice<TRecord> : BoardSlice<stdClass>)
     */
    protected function boardSlice(EloquentBuilder|QueryBuilder $query, string $column): BoardSlice
    {
        $limit = $this->boardLimit($column);

        if ($query instanceof QueryBuilder) {
            return $this->sliceOf((clone $query)->limit($limit + 1)->get(), $limit);
        }

        return $this->sliceOf((clone $query)->limit($limit + 1)->get(), $limit);
    }

    /**
     * @template TValue
     *
     * @param  Collection<int, TValue>  $records
     * @return BoardSlice<TValue>
     */
    private function sliceOf(Collection $records, int $limit): BoardSlice
    {
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

        return is_int($position) ? min(max($position, 0), BoardSlice::END_POSITION) : null;
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
