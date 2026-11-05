<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

final class UnsortedRecordTableComponent extends RecordTableComponent
{
    protected function defaultSort(): ?string
    {
        return null;
    }
}
