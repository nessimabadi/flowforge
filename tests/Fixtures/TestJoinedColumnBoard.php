<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Grouping;

class TestJoinedColumnBoard extends BoardPage
{
    public bool $withoutSelect = false;

    public function getEloquentQuery(): Builder
    {
        $query = Task::query()->join('task_states', 'task_states.task_id', '=', 'tasks.id');

        return $this->withoutSelect ? $query : $query->select('tasks.*', 'task_states.state_value');
    }

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->recordTitleAttribute('title')
            ->columnIdentifier('state_value')
            ->positionIdentifier('order_position')
            ->columns([
                Column::make('open')->label('Open'),
                Column::make('won')->label('Won'),
            ])
            ->groupings([
                Grouping::attribute('tasks.priority')->label('Priority'),
                Grouping::attribute('task_states.state_value')->label('State'),
            ]);
    }
}
