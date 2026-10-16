<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard;

pest()->group('pgsql');

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

test('the count and the write happen under one lock', function () {
    $lock = Cache::lock('flowforge:' . TestBoard::class . ':global:todo', 10);
    $lock->get();

    Sleep::fake();
    Sleep::whenFakingSleep(function () use ($lock) {
        Task::create(['title' => 'Card D', 'status' => 'todo', 'order_position' => '196605.0000000000']);
        $lock->release();
    });

    Livewire::test(TestBoard::class, ['todoLimit' => 2, 'lockWaitSeconds' => 5])
        ->call('moveCard', (string) $this->cardB->id, 'todo')
        ->assertNotified(__('flowforge::flowforge.column_full', ['column' => 'To Do', 'limit' => 2]));

    expect($this->cardB->fresh()->status)->toBe('in_progress')
        ->and(Task::where('status', 'todo')->count())->toBe(2);
});

test('a reorder that went stale is checked against the card as it is now', function () {
    $cardA = Task::where('title', 'Card A')->first();
    $lock = Cache::lock('flowforge:' . TestBoard::class . ':global:todo', 10);
    $lock->get();

    Sleep::fake();
    Sleep::whenFakingSleep(function () use ($cardA, $lock) {
        $cardA->update(['status' => 'completed']);
        Task::create(['title' => 'Card D', 'status' => 'todo', 'order_position' => '196605.0000000000']);
        $lock->release();
    });

    Livewire::test(TestBoard::class, ['todoLimit' => 1, 'lockWaitSeconds' => 5])
        ->call('moveCard', (string) $cardA->id, 'todo')
        ->assertNotified(__('flowforge::flowforge.column_full', ['column' => 'To Do', 'limit' => 1]));

    expect($cardA->fresh()->status)->toBe('completed')
        ->and(Task::where('status', 'todo')->count())->toBe(1);
});

test('the lock is released when the move fails inside it', function () {
    $board = Livewire::test(TestBoard::class, ['todoLimit' => 3, 'failNextPersist' => true])->instance();

    expect($board->moveCard((string) $this->cardB->id, 'todo'))->toBeFalse()
        ->and($this->cardB->fresh()->status)->toBe('in_progress')
        ->and($board->moveCard((string) $this->cardB->id, 'todo'))->toBeTrue()
        ->and($this->cardB->fresh()->status)->toBe('todo');
});

test('a stale card is written back into the column it was asked to join', function () {
    $cardA = Task::where('title', 'Card A')->first();
    $lock = Cache::lock('flowforge:' . TestBoard::class . ':global:todo', 10);
    $lock->get();

    Sleep::fake();
    Sleep::whenFakingSleep(function () use ($cardA, $lock) {
        $cardA->update(['status' => 'completed']);
        $lock->release();
    });

    $moved = Livewire::test(TestBoard::class, ['todoLimit' => 2, 'lockWaitSeconds' => 5])
        ->instance()
        ->moveCard((string) $cardA->id, 'todo');

    expect($moved)->toBeTrue()
        ->and($cardA->fresh()->status)->toBe('todo');
});
