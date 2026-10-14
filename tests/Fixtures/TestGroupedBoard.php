<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Exceptions\CardMoveRejectedException;
use Relaticle\Flowforge\Grouping;
use Relaticle\Flowforge\Lane;

class TestGroupedBoard extends BoardPage
{
    public int $lanesPerPage = 10;

    public int $cardsPerCell = 20;

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
            ->columnIdentifier('status')
            ->positionIdentifier('order_position')
            ->cardsPerColumn($this->cardsPerCell)
            ->lanesPerPage($this->lanesPerPage)
            ->columns([
                Column::make('todo')->label('To Do'),
                Column::make('in_progress')->label('In Progress'),
            ])
            ->groupings([
                Grouping::attribute('priority')->label('Priority'),
                $this->assigneeGrouping(),
                $this->creatorGrouping(),
                Grouping::make('strict')
                    ->label('Strict')
                    ->lanes(fn (): array => [
                        Lane::make('low')->query(fn (Builder $query): Builder => $query->where('priority', 'low')),
                        Lane::make('high')->query(fn (Builder $query): Builder => $query->where('priority', 'high')),
                    ])
                    ->moveUsing(fn () => throw CardMoveRejectedException::because('Nope')),
            ]);
    }

    protected function assigneeGrouping(): Grouping
    {
        return Grouping::make('assignee')
            ->label('Assignee')
            ->lanes(fn (Builder $boardQuery): array => User::query()
                ->whereIn('id', DB::table('task_user')->whereIn('task_id', (clone $boardQuery)->select('tasks.id'))->select('user_id'))
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): Lane => Lane::make($user->id)
                    ->label($user->name)
                    ->query(fn (Builder $query): Builder => $query->whereHas('assignees', fn (Builder $assignees): Builder => $assignees->whereKey($user->id))))
                ->all())
            ->emptyLane('Unassigned', fn (Builder $query): Builder => $query->whereDoesntHave('assignees'))
            ->moveUsing(function (Task $card, Lane $from, Lane $to): void {
                if ($from->getName() !== Grouping::EMPTY_LANE) {
                    $card->assignees()->detach($from->getName());
                }

                if ($to->getName() !== Grouping::EMPTY_LANE) {
                    $card->assignees()->syncWithoutDetaching([$to->getName()]);
                }
            });
    }

    protected function creatorGrouping(): Grouping
    {
        return Grouping::make('creator')
            ->label('Creator')
            ->lanes(fn (Builder $boardQuery): array => User::query()
                ->whereIn('id', (clone $boardQuery)->select('created_by'))
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): Lane => Lane::make($user->id)
                    ->label($user->name)
                    ->query(fn (Builder $query): Builder => $query->where('created_by', $user->id)))
                ->all());
    }
}
