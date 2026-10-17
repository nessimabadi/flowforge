<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardFlex;
use Relaticle\Flowforge\Modern\BoardPage;

class TestCardFlexBoard extends BoardPage
{
    public function getEloquentQuery(): Builder
    {
        return Task::query();
    }

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->recordTitleAttribute('title')
            ->columnIdentifier('status')
            ->positionIdentifier('order_position')
            ->columns([
                Column::make('todo')->label('To Do'),
            ])
            ->cardSchema(fn (Schema $schema): Schema => $schema->components([
                CardFlex::make([
                    TextEntry::make('description'),
                ]),
            ]));
    }
}
