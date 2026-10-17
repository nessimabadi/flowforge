<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests;

use Illuminate\Support\Facades\Artisan;
use Pest\Browser\Playwright\Playwright;

class BrowserTestCase extends TestCase
{
    private static bool $assetsPublished = false;

    protected function setUp(): void
    {
        parent::setUp();

        Playwright::setTimeout(15_000);

        if (! self::$assetsPublished) {
            Artisan::call('filament:assets');
            self::$assetsPublished = true;
        }
    }
}
