<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;

class TestLeftJoinedBoard extends BoardPage
{
    public ?string $onlyPriority = null;

    public function getEloquentQuery(): Builder
    {
        return Task::query()
            ->leftJoin('task_states', 'task_states.task_id', '=', 'tasks.id')
            ->select('tasks.*', 'task_states.state_value')
            ->when($this->onlyPriority, fn (Builder $query, string $priority): Builder => $query->where('tasks.priority', $priority));
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
                Column::make('completed')->label('Completed'),
            ]);
    }
}
