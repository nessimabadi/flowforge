<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Contracts\HasBoard;
use Relaticle\Flowforge\Modern\Concerns\InteractsWithBoard;

/**
 * Standalone Livewire component for testing without Filament Panel.
 */
class TestStandaloneBoard extends Component implements HasActions, HasBoard, HasForms
{
    use InteractsWithActions;
    use InteractsWithBoard {
        InteractsWithBoard::getDefaultActionRecord insteadof InteractsWithActions;
    }
    use InteractsWithForms;

    public bool $showAllColumns = false;

    public function board(Board $board): Board
    {
        return $board
            ->query(Task::query())
            ->recordTitleAttribute('title')
            ->columnIdentifier('status')
            ->positionIdentifier('order_position')
            ->columns([
                Column::make('todo')->label('To Do')->color('gray'),
                Column::make('hidden_column')->visible(fn () => $this->showAllColumns)->label('Hidden Column'),
                Column::make('in_progress')->label('In Progress')->color('blue'),
                Column::make('completed')->label('Completed')->color('green'),
            ]);
    }

    public function render()
    {
        return <<<'BLADE'
        <div>
            {{ $this->board }}
        </div>
        BLADE;
    }
}
