<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardAvatar;
use Relaticle\Flowforge\Components\CardMeta;
use Relaticle\Flowforge\Components\CardTag;
use Relaticle\Flowforge\Components\CardTitle;

class TestCardComponentsBoard extends BoardPage
{
    public function getEloquentQuery(): Builder
    {
        return Task::query()->with('assignees');
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
                Column::make('in_progress')->label('In Progress'),
            ])
            ->cardSchema(fn (Schema $schema): Schema => $schema->components([
                CardTag::make('priority')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'high' ? 'danger' : 'gray'),
                CardTitle::make(),
                CardMeta::make([
                    CardTag::make('status'),
                ]),
                CardAvatar::make('assignees')
                    ->limit(3)
                    ->nameUsing(fn (Model $person): string => $person->name),
            ]));
    }
}
