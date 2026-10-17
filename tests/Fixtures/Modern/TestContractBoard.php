<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardAvatar;
use Relaticle\Flowforge\Components\CardFlex;
use Relaticle\Flowforge\Components\CardMeta;
use Relaticle\Flowforge\Components\CardTag;
use Relaticle\Flowforge\Components\CardTitle;
use Relaticle\Flowforge\Grouping;
use Relaticle\Flowforge\Modern\BoardPage;

class TestContractBoard extends BoardPage
{
    public bool $withHeaderToolbar = false;

    public function getEloquentQuery(): Builder
    {
        return Task::query()->with('assignees');
    }

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->recordTitleAttribute('title')
            ->searchable(['title'])
            ->headerToolbar($this->withHeaderToolbar)
            ->collapseEmptyColumns()
            ->columnIdentifier('status')
            ->positionIdentifier('order_position')
            ->columnSummary(fn (Builder $columnQuery): string => $columnQuery->count() . ' shown')
            ->createCardAction(
                CreateAction::make('createTask')
                    ->model(Task::class)
                    ->schema([TextInput::make('title')->required()])
                    ->mutateDataUsing(fn (array $data, array $arguments): array => [...$data, 'status' => $arguments['column'] ?? 'todo']),
            )
            ->columns([
                Column::make('todo')->label('To Do')->color('gray')->limit(1),
                Column::make('in_progress')->label('In Progress')->color('blue'),
                Column::make('completed')->label('Completed'),
            ])
            ->groupings([Grouping::attribute('priority')->label('Priority')])
            ->cardActions([Action::make('editTask')->action(function (): void {})])
            ->cardSchema(fn (Schema $schema): Schema => $schema->components([
                CardTag::make('priority'),
                CardTitle::make(),
                CardMeta::make([
                    CardTag::make('status'),
                    TextEntry::make('description'),
                ]),
                CardFlex::make([CardTag::make('priority'), CardAvatar::make('assignees')]),
                CardAvatar::make('assignees')->nameUsing(fn (User $user): string => $user->name),
            ]));
    }
}
