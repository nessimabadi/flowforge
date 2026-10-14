<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Schemas\Schema;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Components\CardTitle;

class TestCardActionTitleBoard extends TestCardActionBoard
{
    public function board(Board $board): Board
    {
        return parent::board($board)
            ->cardSchema(fn (Schema $schema): Schema => $schema->components([
                CardTitle::make(),
            ]));
    }
}
