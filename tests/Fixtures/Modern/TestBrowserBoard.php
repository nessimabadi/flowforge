<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

class TestBrowserBoard extends TestBoard
{
    public ?int $todoLimit = 3;

    public bool $collapseEmpty = true;
}
