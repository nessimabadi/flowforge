<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Modern\BoardPage;

class TestQueryShapeBoard extends BoardPage
{
    public string $shape = 'plain';

    public function getEloquentQuery(): Builder
    {
        return match ($this->shape) {
            'labelled' => Task::query()
                ->leftJoin('task_labels', 'task_labels.task_id', '=', 'tasks.id')
                ->select('tasks.*', 'task_labels.label'),
            'unselected' => Task::query()
                ->leftJoin('task_labels', 'task_labels.task_id', '=', 'tasks.id'),
            default => Task::query(),
        };
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
