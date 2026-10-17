<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestQueryShapeBoard;

function labelledModernTask(string $title, string $status, string $position, bool $labelled = true): Task
{
    $task = Task::create(['title' => $title, 'status' => $status, 'order_position' => $position]);

    if ($labelled) {
        DB::table('task_labels')->insert(['id' => $task->id + 100000, 'task_id' => $task->id, 'label' => 'bug']);
    }

    return $task;
}

test('a board whose query joins a labelled table renders', function () {
    labelledModernTask('Joined card', 'todo', '65535.0000000000');

    Livewire::test(TestQueryShapeBoard::class, ['shape' => 'labelled'])
        ->assertSee('Joined card');
})->group('pgsql');

test('a card moves to the bottom of a column on a board that joins a labelled table', function () {
    labelledModernTask('Existing', 'in_progress', '1000.0000000000');
    $card = labelledModernTask('Moving', 'todo', '65535.0000000000', labelled: false);

    Livewire::test(TestQueryShapeBoard::class, ['shape' => 'labelled'])
        ->call('moveCard', (string) $card->id, 'in_progress')
        ->assertDispatched('kanban-card-moved');

    expect($card->fresh()->status)->toBe('in_progress')
        ->and((float) $card->fresh()->order_position)->toBeGreaterThan(1000.0);
})->group('pgsql');

test('a card moves between two cards of a board that joins a labelled table', function () {
    $above = labelledModernTask('Above', 'in_progress', '1000.0000000000');
    $below = labelledModernTask('Below', 'in_progress', '2000.0000000000', labelled: false);
    $card = labelledModernTask('Moving', 'todo', '65535.0000000000');

    Livewire::test(TestQueryShapeBoard::class, ['shape' => 'labelled'])
        ->call('moveCard', (string) $card->id, 'in_progress', (string) $above->id, (string) $below->id)
        ->assertDispatched('kanban-card-moved');

    expect((float) $card->fresh()->order_position)->toBeGreaterThan(1000.0)->toBeLessThan(2000.0);
})->group('pgsql');

test('a column rebalances on a board that joins a labelled table', function () {
    $above = labelledModernTask('Above', 'in_progress', '1000.0000000000');
    $below = labelledModernTask('Below', 'in_progress', '1000.0000500000', labelled: false);
    $card = labelledModernTask('Moving', 'todo', '65535.0000000000');

    Livewire::test(TestQueryShapeBoard::class, ['shape' => 'labelled'])
        ->call('moveCard', (string) $card->id, 'in_progress', (string) $above->id, (string) $below->id)
        ->assertDispatched('kanban-card-moved');

    expect((float) $below->fresh()->order_position - (float) $above->fresh()->order_position)->toBeGreaterThan(1.0);
})->group('pgsql');
