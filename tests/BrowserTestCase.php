<?php

declare(strict_types=1);

namespace Odden\Ui\Tests;

use Odden\Ui\Tests\Support\RecordTableFixtures;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class BrowserTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), WorkbenchServiceProvider::class];
    }

    protected function setUp(): void
    {
        $this->afterApplicationCreated(RecordTableFixtures::install(...));

        parent::setUp();
    }
}
