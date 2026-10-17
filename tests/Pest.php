<?php

use Relaticle\Flowforge\Tests\BrowserTestCase;
use Relaticle\Flowforge\Tests\StandaloneTestCase;
use Relaticle\Flowforge\Tests\TestCase;

pest()->extends(TestCase::class)->in('Feature', 'Unit', 'Modern/Feature', 'Modern/Unit');
pest()->extends(StandaloneTestCase::class)->in('Standalone', 'Modern/Standalone');
pest()->extends(BrowserTestCase::class)->in('Browser');

// The browser plugin rmdirs this folder on every run and warns when it is missing, which failOnWarning turns into exit code 1.
if (! is_dir(__DIR__ . '/Browser/Traces')) {
    mkdir(__DIR__ . '/Browser/Traces');
}
