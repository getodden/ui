<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Workbench\App\ImportMap;
use Workbench\App\Livewire\BoardDemo;
use Workbench\App\Livewire\Demo;
use Workbench\App\Livewire\EditorDemo;
use Workbench\App\Livewire\RepeaterDemo;
use Workbench\App\Livewire\TableDemo;

final class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'workbench');

        Livewire::component('demo', Demo::class);
        Livewire::component('board-demo', BoardDemo::class);
        Livewire::component('editor-demo', EditorDemo::class);
        Livewire::component('repeater-demo', RepeaterDemo::class);
        Livewire::component('table-demo', TableDemo::class);
        Route::middleware('web')->get('/', Demo::class);
        Route::get('__workbench/{root}/{path}', fn (string $root, string $path) => ImportMap::response($root, $path))->where('root', 'js|npm')->where('path', '.*');
    }
}
