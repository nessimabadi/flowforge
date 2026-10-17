<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

class TestCardActionModalBoard extends TestCardActionBoard
{
    protected function cardActionName(): string
    {
        return 'edit';
    }
}
