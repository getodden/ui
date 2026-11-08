<?php

declare(strict_types=1);

namespace Odden\Ui\Livewire\Concerns;

use Odden\Ui\Enums\RepeaterDirection;
use Odden\Ui\Support\RepeaterRows;
use Odden\Ui\Support\RepeaterSpec;

trait WithRepeater
{
    /**
     * @return array<string, RepeaterSpec>
     */
    abstract protected function repeaters(): array;

    public function addRepeaterRow(string $property): void
    {
        $spec = $this->repeaterSpec($property);

        if ($spec !== null) {
            $this->{$property} = RepeaterRows::add($this->{$property}, $spec);
        }
    }

    public function removeRepeaterRow(string $property, string $key): void
    {
        $spec = $this->repeaterSpec($property);

        if ($spec !== null) {
            $this->{$property} = RepeaterRows::remove($this->{$property}, $spec, $key);
        }
    }

    public function moveRepeaterRow(string $property, string $key, string $direction): void
    {
        $direction = RepeaterDirection::tryFrom($direction);

        if ($direction !== null && $this->repeaterSpec($property) !== null) {
            $this->{$property} = RepeaterRows::move($this->{$property}, $key, $direction);
        }
    }

    /**
     * @param  array<mixed>  $keys
     */
    public function reorderRepeaterRows(string $property, array $keys): void
    {
        if ($this->repeaterSpec($property) !== null) {
            $this->{$property} = RepeaterRows::reorder($this->{$property}, $keys);
        }
    }

    public function bootedWithRepeater(): void
    {
        $this->keyRepeaterRows();
    }

    public function updatedWithRepeater(string $path): void
    {
        $this->keyRepeaterRows();
    }

    protected function keyRepeaterRows(): void
    {
        foreach (array_keys($this->repeaters()) as $property) {
            if ($this->repeaterSpec((string) $property) !== null) {
                $this->{$property} = RepeaterRows::withKeys($this->{$property});
            }
        }
    }

    private function repeaterSpec(string $property): ?RepeaterSpec
    {
        $spec = $this->repeaters()[$property] ?? null;

        return $spec instanceof RepeaterSpec && property_exists($this, $property) && is_array($this->{$property}) ? $spec : null;
    }
}
