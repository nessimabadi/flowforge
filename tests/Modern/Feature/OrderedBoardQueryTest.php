<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestQueryShapeBoard;

beforeEach(function () {
    Task::create(['title' => 'Alpha', 'status' => 'todo', 'priority' => 'low', 'order_position' => '65535.0000000000']);
    Task::create(['title' => 'Beta', 'status' => 'in_progress', 'priority' => 'high', 'order_position' => '65535.0000000000']);
});

test('a board whose query is ordered renders its cards', function (string $shape) {
    Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape])
        ->assertSee('Alpha')
        ->assertSee('Beta');
})->with(['ordered', 'counted'])->group('pgsql');

test('a grouped board whose query is ordered renders its lanes', function (string $shape) {
    Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape])
        ->set('groupBy', 'priority')
        ->assertSee('Alpha')
        ->assertSee('Beta');
})->with(['ordered', 'counted'])->group('pgsql');

test('a grouped board whose query is ordered lists the lanes beyond the visible ones', function (string $shape) {
    Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape, 'lanesPerPage' => 1])
        ->set('groupBy', 'priority')
        ->assertSee('Beta')
        ->assertDontSee('Alpha');
})->with(['ordered', 'counted'])->group('pgsql');

test('a column limit and a summary work on an ordered board query', function (string $shape) {
    Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape, 'todoLimit' => 1, 'withSummary' => true])
        ->assertSeeHtml('ff-summary">1 shown</span>')
        ->call('moveCard', (string) Task::where('title', 'Beta')->value('id'), 'todo')
        ->assertNotified(__('flowforge::flowforge.column_full', ['column' => 'To Do', 'limit' => 1]));
})->with(['ordered', 'counted'])->group('pgsql');

test('loading more cards on an ordered board query counts the column', function (string $shape) {
    $board = Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape])->instance();

    expect($board->isColumnFullyLoaded('todo'))->toBeTrue();
})->with(['ordered', 'counted'])->group('pgsql');
