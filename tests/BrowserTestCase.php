<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests;

use Illuminate\Support\Facades\Artisan;

class BrowserTestCase extends TestCase
{
    private static bool $assetsPublished = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$assetsPublished) {
            Artisan::call('filament:assets');
            self::$assetsPublished = true;
        }
    }
}
