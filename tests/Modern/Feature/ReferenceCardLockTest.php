<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestQueryShapeBoard;

beforeEach(function () {
    $this->card = Task::create(['title' => 'Card', 'status' => 'todo', 'order_position' => '65535.0000000000']);
});

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
