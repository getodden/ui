<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

use InvalidArgumentException;

final readonly class RepeaterSpec
{
    /**
     * @param  array<string, mixed>  $blank
     */
    public function __construct(
        public int $min = 0,
        public ?int $max = null,
        public array $blank = [],
    ) {
        if ($min < 0 || ($max !== null && $max < $min)) {
            throw new InvalidArgumentException('A repeater needs a minimum of zero or more and a maximum of at least the minimum.');
        }
    }
}
