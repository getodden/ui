<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Workbench\App\Models\Fruit;

final class RecordTableFixtures
{
    public const array FRUIT = [
        ['Apple', 'red', 120],
        ['Apricot', 'orange', 210],
        ['Avocado', 'green', 250],
        ['Banana', 'yellow', 90],
        ['Blackberry', 'purple', 480],
        ['Blueberry', 'purple', 520],
        ['Cantaloupe', 'orange', 300],
        ['Cherry', 'red', 450],
        ['Clementine', 'orange', 140],
        ['Cranberry', 'red', 380],
        ['Date', 'brown', 600],
        ['Dragonfruit', 'red', 700],
        ['Fig', 'purple', 550],
        ['Grape', 'green', 260],
        ['Grapefruit', 'orange', 170],
        ['Guava', 'green', 330],
        ['Kiwi', 'green', 150],
        ['Lemon', 'yellow', 80],
        ['Lime', 'green', 70],
        ['Lychee', 'red', 640],
        ['Mango', 'orange', 280],
        ['Melon', 'green', 310],
        ['Nectarine', 'orange', 240],
        ['Orange', 'orange', 110],
        ['Papaya', 'orange', 360],
        ['Peach', 'orange', 220],
        ['Pear', 'green', 130],
        ['Pineapple', 'yellow', 340],
        ['Plum', 'purple', 200],
        ['Raspberry', 'red', 500],
    ];

    public static function install(): void
    {
        Schema::dropIfExists('fruits');

        Schema::create('fruits', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('colour');
            $table->unsignedInteger('price');
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (self::FRUIT as [$name, $colour, $price]) {
            Fruit::query()->create(['name' => $name, 'colour' => $colour, 'price' => $price]);
        }
    }
}
