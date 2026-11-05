<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class CustomTiebreakRecordTableComponent extends RecordTableComponent
{
    protected function tiebreakColumn(Builder|QueryBuilder $query): ?string
    {
        return 'name';
    }
}
