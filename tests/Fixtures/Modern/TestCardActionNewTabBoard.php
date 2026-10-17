<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

class TestCardActionNewTabBoard extends TestCardActionBoard
{
    protected function cardActionName(): string
    {
        return 'viewInNewTab';
    }
}
