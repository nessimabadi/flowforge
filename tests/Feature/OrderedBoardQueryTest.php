<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestQueryShapeBoard;

beforeEach(function () {
    Task::create(['title' => 'Alpha', 'status' => 'todo', 'order_position' => '65535.0000000000']);
    Task::create(['title' => 'Beta', 'status' => 'in_progress', 'order_position' => '65535.0000000000']);
});

test('a board whose query is ordered renders its cards', function (string $shape) {
    Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape])
        ->assertSee('Alpha')
        ->assertSee('Beta');
})->with(['ordered', 'counted'])->group('pgsql');

test('loading more cards on an ordered board query counts the column', function (string $shape) {
    $board = Livewire::test(TestQueryShapeBoard::class, ['shape' => $shape])->instance();

    expect($board->isColumnFullyLoaded('todo'))->toBeTrue();
})->with(['ordered', 'counted'])->group('pgsql');
