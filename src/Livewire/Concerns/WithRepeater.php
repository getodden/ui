<?php

declare(strict_types=1);

namespace Odden\Ui\Livewire\Concerns;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Odden\Ui\Enums\RepeaterDirection;
use Odden\Ui\Support\RepeaterRows;
use Odden\Ui\Support\RepeaterSpec;

trait WithRepeater
{
    /**
     * @var array<string, int>
     */
    protected array $repeaterCountsBeforeUpdate = [];

    /**
     * @return array<string, RepeaterSpec>
     */
    abstract protected function repeaters(): array;

    public function addRepeaterRow(string $property): void
    {
        $spec = $this->repeaterSpec($property);

        if ($spec !== null) {
            $this->changeRepeaterRows($property, fn (array $rows): array => RepeaterRows::add($rows, $spec));
        }
    }

    public function removeRepeaterRow(string $property, string $key): void
    {
        $spec = $this->repeaterSpec($property);

        if ($spec !== null) {
            $this->changeRepeaterRows($property, fn (array $rows): array => RepeaterRows::remove($rows, $spec, $key));
        }
    }

    public function moveRepeaterRow(string $property, string $key, string $direction): void
    {
        $direction = RepeaterDirection::tryFrom($direction);

        if ($direction !== null && $this->repeaterSpec($property) !== null) {
            $this->changeRepeaterRows($property, fn (array $rows): array => RepeaterRows::move($rows, $key, $direction));
        }
    }

    /**
     * @param  array<mixed>  $keys
     */
    public function reorderRepeaterRows(string $property, array $keys): void
    {
        if ($this->repeaterSpec($property) !== null) {
            $this->changeRepeaterRows($property, fn (array $rows): array => RepeaterRows::reorder($rows, $keys));
        }
    }

    public function bootedWithRepeater(): void
    {
        $this->keyRepeaterRows();
    }

    public function updatingWithRepeater(string $path): void
    {
        $property = Str::before($path, '.');

        if ($this->repeaterSpec($property) !== null) {
            $this->repeaterCountsBeforeUpdate[$property] = count($this->{$property});
        }
    }

    public function updatedWithRepeater(string $path): void
    {
        $property = Str::before($path, '.');
        $spec = $this->repeaterSpec($property);

        if ($spec === null) {
            return;
        }

        $countBefore = $this->repeaterCountsBeforeUpdate[$property] ?? null;
        unset($this->repeaterCountsBeforeUpdate[$property]);

        $submitted = array_values($this->{$property});
        $before = RepeaterRows::withKeys(array_map(fn (mixed $row): array => is_array($row) ? $row : [], $submitted));
        $kept = array_values(array_filter($before, fn (array $row, int $position): bool => is_array($submitted[$position]), ARRAY_FILTER_USE_BOTH));
        $after = count($kept) === $countBefore ? $kept : RepeaterRows::within($kept, $spec);

        $this->{$property} = $after;

        $this->moveRepeaterErrors($property, array_column($before, RepeaterRows::KEY), array_column($after, RepeaterRows::KEY));
    }

    public function renderingWithRepeater(?View $view = null): void
    {
        $this->keyRepeaterRows();

        if ($view === null) {
            return;
        }

        foreach (array_keys($this->repeaters()) as $property) {
            if ($this->repeaterSpec((string) $property) !== null) {
                $view->with((string) $property, $this->{$property});
            }
        }
    }

    protected function keyRepeaterRows(): void
    {
        foreach (array_keys($this->repeaters()) as $property) {
            if ($this->repeaterSpec((string) $property) !== null) {
                $this->{$property} = RepeaterRows::withKeys($this->{$property});
            }
        }
    }

    /**
     * @param  Closure(list<array<string, mixed>>): list<array<string, mixed>>  $change
     */
    private function changeRepeaterRows(string $property, Closure $change): void
    {
        $before = RepeaterRows::withKeys($this->{$property});
        $after = $change($before);

        $this->{$property} = $after;

        $this->moveRepeaterErrors($property, array_column($before, RepeaterRows::KEY), array_column($after, RepeaterRows::KEY));
    }

    /**
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    private function moveRepeaterErrors(string $property, array $before, array $after): void
    {
        if ($before === $after) {
            return;
        }

        $errors = new MessageBag;

        foreach ($this->getErrorBag()->messages() as $field => $messages) {
            $moved = $this->movedRepeaterField($property, (string) $field, $before, $after);

            if ($moved !== null) {
                $errors->merge([$moved => $messages]);
            }
        }

        $this->setErrorBag($errors);
    }

    /**
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    private function movedRepeaterField(string $property, string $field, array $before, array $after): ?string
    {
        if (preg_match('/^'.preg_quote($property, '/').'\.(\d+)(\..*)?$/', $field, $match) !== 1) {
            return $field;
        }

        $position = isset($before[(int) $match[1]]) ? array_search($before[(int) $match[1]], $after, true) : false;

        return $position === false ? null : $property.'.'.$position.($match[2] ?? '');
    }

    protected function repeaterSpec(string $property): ?RepeaterSpec
    {
        $spec = $this->repeaters()[$property] ?? null;

        return $spec instanceof RepeaterSpec && property_exists($this, $property) && is_array($this->{$property}) ? $spec : null;
    }
}
