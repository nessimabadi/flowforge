<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestLeftJoinedBoard;

beforeEach(function () {
    $this->card = Task::create(['title' => 'Card', 'status' => 'todo', 'order_position' => '65535.0000000000']);
});

test('a card moves between two cards of a board whose query left-joins another table', function () {
    $above = Task::create(['title' => 'Above', 'status' => 'in_progress', 'order_position' => '1000.0000000000']);
    $below = Task::create(['title' => 'Below', 'status' => 'in_progress', 'order_position' => '2000.0000000000']);
    DB::table('task_states')->insert(['task_id' => $above->id, 'state_value' => 'open']);

    Livewire::test(TestLeftJoinedBoard::class)
        ->call('moveCard', (string) $this->card->id, 'in_progress', (string) $above->id, (string) $below->id)
        ->assertDispatched('kanban-card-moved');

    expect($this->card->fresh()->status)->toBe('in_progress')
        ->and((float) $this->card->fresh()->order_position)->toBeGreaterThan(1000.0)->toBeLessThan(2000.0);
})->group('pgsql');

test('a reference card outside the board query does not place the moved card', function () {
    $outside = Task::create(['title' => 'Outside', 'status' => 'in_progress', 'priority' => 'low', 'order_position' => '9000000.0000000000']);

    Livewire::test(TestLeftJoinedBoard::class, ['onlyPriority' => 'medium'])
        ->call('moveCard', (string) $this->card->id, 'in_progress', (string) $outside->id)
        ->assertDispatched('kanban-card-moved');

    expect((float) $this->card->fresh()->order_position)->toBeLessThan(9000000.0);
});
