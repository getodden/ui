<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

final class Fruit extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['price' => 'integer'];
    }
}
