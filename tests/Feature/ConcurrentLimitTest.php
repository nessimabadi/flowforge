<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    Task::create(['title' => 'Card A', 'status' => 'todo', 'order_position' => '65535.0000000000']);
    $this->cardB = Task::create(['title' => 'Card B', 'status' => 'in_progress', 'order_position' => '65535.0000000000']);
    $this->cardC = Task::create(['title' => 'Card C', 'status' => 'in_progress', 'order_position' => '131070.0000000000']);
});

test('two moves into a column with one free slot: the second is refused', function () {
    $board = Livewire::test(TestBoard::class, ['todoLimit' => 2])->instance();

    expect($board->moveCard((string) $this->cardB->id, 'todo'))->toBeTrue()
        ->and($board->moveCard((string) $this->cardC->id, 'todo'))->toBeFalse()
        ->and(Task::where('status', 'todo')->count())->toBe(2);
});

test('a move gives up with a message while another move holds the column', function () {
    $lock = Cache::lock('flowforge:' . TestBoard::class . ':global:todo', 10);
    $lock->get();

    Livewire::test(TestBoard::class, ['todoLimit' => 3, 'lockWaitSeconds' => 0])
        ->call('moveCard', (string) $this->cardB->id, 'todo')
        ->assertNotified(__('flowforge::flowforge.board_busy', ['column' => 'To Do']));

    $lock->release();

    expect($this->cardB->fresh()->status)->toBe('in_progress');
});
