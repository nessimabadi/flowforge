<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardAvatar;
use Relaticle\Flowforge\Components\CardMeta;
use Relaticle\Flowforge\Components\CardTag;
use Relaticle\Flowforge\Components\CardTitle;
use Relaticle\Flowforge\Modern\BoardPage;

class TestCardComponentsBoard extends BoardPage
{
    public function getEloquentQuery(): Builder
    {
        return Task::query()->with(['assignees', 'creator']);
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
                CardTag::make('labels')
                    ->color(fn (string $state): string => $state === 'bug' ? 'danger' : 'gray'),
                CardTitle::make(),
                CardMeta::make([
                    CardTag::make('status'),
                    TextEntry::make('description'),
                ]),
                CardAvatar::make('assignees')
                    ->limit(3)
                    ->nameUsing(fn (User $user): string => strtoupper($user->name)),
                CardAvatar::make('creator')
                    ->avatarUrlUsing(fn (User $user): string => "https://example.test/avatars/{$user->id}.png"),
            ]));
    }
}
