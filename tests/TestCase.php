<?php

declare(strict_types=1);

namespace Odden\Ui\Tests;

use BladeUI\Icons\BladeIconsServiceProvider;
use Livewire\LivewireServiceProvider;
use Odden\Ui\UiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use OwenVoke\BladeFontAwesome\BladeFontAwesomeServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeFontAwesomeServiceProvider::class,
            UiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
    }
}
