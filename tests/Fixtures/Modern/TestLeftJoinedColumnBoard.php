<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Modern\BoardPage;

class TestLeftJoinedColumnBoard extends BoardPage
{
    public function getEloquentQuery(): Builder
    {
        return Task::query()
            ->leftJoin('task_states', 'task_states.task_id', '=', 'tasks.id')
            ->select('tasks.*', 'task_states.state_value');
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
            ]);
    }

    protected function persistCardMove(Model $card, mixed $columnValue, string $position): void
    {
        $card->update([$this->getBoard()->getPositionIdentifierAttribute() => $position]);

        DB::table('task_states')->updateOrInsert(
            ['task_id' => $card->getKey()],
            ['state_value' => $columnValue],
        );
    }
}
