<?php

use Relaticle\Flowforge\Tests\BrowserTestCase;
use Relaticle\Flowforge\Tests\StandaloneTestCase;
use Relaticle\Flowforge\Tests\TestCase;

pest()->extends(TestCase::class)->in('Feature', 'Unit', 'Modern/Feature', 'Modern/Unit');
pest()->extends(StandaloneTestCase::class)->in('Standalone', 'Modern/Standalone');
pest()->extends(BrowserTestCase::class)->in('Browser');
