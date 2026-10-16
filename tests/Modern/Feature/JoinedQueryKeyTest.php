<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestQueryShapeBoard;

function labelledModernTask(string $title, string $status, string $position): Task
{
    $task = Task::create(['title' => $title, 'status' => $status, 'order_position' => $position]);
    DB::table('task_labels')->insert(['id' => $task->id, 'task_id' => $task->id, 'label' => 'bug']);

    return $task;
}

test('a board whose query joins a table with its own id renders', function (string $shape) {
    labelledModernTask('Joined card', 'todo', '65535.0000000000');

    Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape])
        ->assertSee('Joined card');
})->with(['labelled', 'unselected'])->group('pgsql');

test('a card moves to the bottom of a column on a board that joins a table with its own id', function (string $shape) {
    labelledModernTask('Existing', 'in_progress', '1000.0000000000');
    $card = labelledModernTask('Moving', 'todo', '65535.0000000000');

    Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape])
        ->call('moveCard', (string) $card->id, 'in_progress')
        ->assertDispatched('kanban-card-moved');

    expect((float) $card->fresh()->order_position)->toBeGreaterThan(1000.0);
})->with(['labelled', 'unselected'])->group('pgsql');

test('a column rebalances on a board that joins a table with its own id', function (string $shape) {
    $above = labelledModernTask('Above', 'in_progress', '1000.0000000000');
    $below = labelledModernTask('Below', 'in_progress', '1000.0000500000');
    $card = labelledModernTask('Moving', 'todo', '65535.0000000000');

    Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape])
        ->call('moveCard', (string) $card->id, 'in_progress', (string) $above->id, (string) $below->id)
        ->assertDispatched('kanban-card-moved');

    expect((float) $below->fresh()->order_position - (float) $above->fresh()->order_position)->toBeGreaterThan(1.0);
})->with(['labelled', 'unselected'])->group('pgsql');
