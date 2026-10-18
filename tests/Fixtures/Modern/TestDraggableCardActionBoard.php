<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Relaticle\Flowforge\Board;

class TestDraggableCardActionBoard extends TestCardActionModalBoard
{
    public function board(Board $board): Board
    {
        return parent::board($board)->positionIdentifier('order_position');
    }
}
