<?php

declare(strict_types=1);

use Odden\Ui\Tests\BrowserTestCase;
use Odden\Ui\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');
pest()->extend(BrowserTestCase::class)->in('Browser');
