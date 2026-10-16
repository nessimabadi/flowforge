<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestLeftJoinedBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestQueryShapeBoard;

beforeEach(function () {
    $this->card = Task::create(['title' => 'Card', 'status' => 'todo', 'order_position' => '65535.0000000000']);
});

test('a card moves between two cards of a board whose query left-joins another table', function () {
    $above = Task::create(['title' => 'Above', 'status' => 'in_progress', 'order_position' => '1000.0000000000']);
    $below = Task::create(['title' => 'Below', 'status' => 'in_progress', 'order_position' => '2000.0000000000']);
    DB::table('task_states')->insert(['task_id' => $above->id, 'state_value' => 'open']);

    Livewire::test(TestLeftJoinedBoard::class)
        ->assertSee('Above')
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
})->group('pgsql');

test('a board query that orders or counts still locks the reference card', function (string $shape) {
    $above = Task::create(['title' => 'Above', 'status' => 'in_progress', 'order_position' => '1000.0000000000']);
    $board = Livewire::test(TestQueryShapeBoard::class)->instance();
    $query = match ($shape) {
        'distinct' => Task::query()->select('tasks.id', 'tasks.created_at')->distinct()->orderBy('created_at'),
        'counted' => Task::query()->withCount('project')->orderBy('project_count'),
    };

    $locked = DB::transaction(fn () => (fn () => $this->lockBoardCard($query, (string) $above->id))->call($board));

    expect($locked?->getKey())->toBe($above->getKey());
})->with(['distinct', 'counted'])->group('pgsql');
