<?php

declare(strict_types=1);

namespace Odden\Ui;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class UiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $views = __DIR__.'/../resources/views';

        $this->loadViewsFrom($views, 'ui');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'ui');

        Blade::anonymousComponentPath($views.'/components', 'ui');
    }
}
