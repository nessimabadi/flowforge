<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

class TestCardActionHiddenModalBoard extends TestCardActionBoard
{
    protected function cardActionName(): string
    {
        return 'hiddenEdit';
    }
}
