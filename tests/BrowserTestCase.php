<?php

declare(strict_types=1);

namespace Odden\Ui\Tests;

use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class BrowserTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), WorkbenchServiceProvider::class];
    }
}
