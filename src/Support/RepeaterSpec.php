<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

final readonly class RepeaterSpec
{
    /**
     * @param  array<string, mixed>  $blank
     */
    public function __construct(
        public int $min = 0,
        public ?int $max = null,
        public array $blank = [],
    ) {}
}
