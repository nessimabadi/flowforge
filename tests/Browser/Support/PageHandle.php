<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Browser\Support;

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Playwright\Page;
use Pest\Browser\Support\ComputeUrl;

final class PageHandle
{
    public static function of(AwaitableWebpage $webpage): Page
    {
        return $webpage->page();
    }

    public static function navigateWithInitScript(AwaitableWebpage $webpage, string $path, string $initScript): void
    {
        $page = self::of($webpage);

        $page->context()->addInitScript($initScript);
        $page->goto(ComputeUrl::from($path));
    }
}
