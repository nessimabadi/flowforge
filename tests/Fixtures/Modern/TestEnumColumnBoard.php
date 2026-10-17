<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Grouping;
use Relaticle\Flowforge\Modern\BoardPage;

class TestEnumColumnBoard extends BoardPage
{
    public bool $pureEnum = true;

    public function getEloquentQuery(): Builder
    {
        return $this->pureEnum ? PlainEnumTask::query() : BackedEnumTask::query();
    }

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->recordTitleAttribute('title')
            ->columnIdentifier('status')
            ->positionIdentifier('order_position')
            ->columns($this->pureEnum
                ? [Column::make('Review'), Column::make('Shipped')]
                : [Column::make('active'), Column::make('pending')])
            ->groupings([Grouping::attribute('priority')->label('Priority')]);
    }
}
