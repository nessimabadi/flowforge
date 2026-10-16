<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Grouping;
use Relaticle\Flowforge\Modern\BoardPage;

class TestQueryShapeBoard extends BoardPage
{
    public string $shape = 'plain';

    public ?int $todoLimit = null;

    public bool $withSummary = false;

    public int $lanesPerPage = 10;

    public function getEloquentQuery(): Builder
    {
        return match ($this->shape) {
            'labelled' => Task::query()
                ->leftJoin('task_labels', 'task_labels.task_id', '=', 'tasks.id')
                ->select('tasks.*', 'task_labels.label'),
            'ordered' => Task::query()->orderBy('created_at'),
            'counted' => Task::query()->withCount('project')->orderBy('project_count'),
            default => Task::query(),
        };
    }

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->recordTitleAttribute('title')
            ->columnSummary($this->withSummary ? fn (Builder $columnQuery): string => $columnQuery->count() . ' shown' : null)
            ->lanesPerPage($this->lanesPerPage)
            ->columnIdentifier('status')
            ->positionIdentifier('order_position')
            ->columns([
                Column::make('todo')->label('To Do')->limit($this->todoLimit),
                Column::make('in_progress')->label('In Progress'),
                Column::make('completed')->label('Completed'),
            ])
            ->groupings([
                Grouping::attribute('priority')->label('Priority'),
            ]);
    }
}
