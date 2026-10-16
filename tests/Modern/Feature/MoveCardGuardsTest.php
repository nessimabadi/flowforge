<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard;

pest()->group('pgsql');

beforeEach(function () {
    $this->cardA = Task::create(['title' => 'Card A', 'status' => 'todo', 'order_position' => '65535.0000000000']);
    $this->cardB = Task::create(['title' => 'Card B', 'status' => 'in_progress', 'order_position' => '65535.0000000000']);
});

test('a move returns true and writes the column', function () {
    $moved = Livewire::test(TestBoard::class)->instance()->moveCard((string) $this->cardA->id, 'in_progress');

    expect($moved)->toBeTrue()
        ->and($this->cardA->fresh()->status)->toBe('in_progress');
});

test('a move into a column the board does not show is refused with a message', function () {
    Livewire::test(TestBoard::class)
        ->call('moveCard', (string) $this->cardA->id, 'archived')
        ->assertNotified(__('flowforge::flowforge.unknown_column'));

    expect($this->cardA->fresh()->status)->toBe('todo');
});

test('a card that is gone is refused with a message', function () {
    $cardId = (string) $this->cardA->id;
    $this->cardA->delete();

    $board = Livewire::test(TestBoard::class)->instance();

    expect($board->moveCard($cardId, 'in_progress'))->toBeFalse();

    Livewire::test(TestBoard::class)
        ->call('moveCard', $cardId, 'in_progress')
        ->assertNotified(__('flowforge::flowforge.unknown_card'));
});

test('a refused move returns false', function () {
    $moved = Livewire::test(TestBoard::class)->instance()->moveCard((string) $this->cardA->id, 'archived');

    expect($moved)->toBeFalse();
});

test('authorization runs before anything is written', function () {
    Livewire::test(TestBoard::class, ['denyMovesFor' => 'Card A'])
        ->call('moveCard', (string) $this->cardA->id, 'in_progress')
        ->assertForbidden();

    expect($this->cardA->fresh()->status)->toBe('todo');
});

test('an app can replace how a move is written', function () {
    Livewire::test(TestBoard::class, ['recordPersist' => true])
        ->call('moveCard', (string) $this->cardA->id, 'in_progress');

    expect($this->cardA->fresh())
        ->status->toBe('in_progress')
        ->priority->toBe('moved');
});

test('a full column refuses a card from another column', function () {
    Livewire::test(TestBoard::class, ['todoLimit' => 1])
        ->call('moveCard', (string) $this->cardB->id, 'todo')
        ->assertNotified(__('flowforge::flowforge.column_full', ['column' => 'To Do', 'limit' => 1]));

    expect($this->cardB->fresh()->status)->toBe('in_progress');
});

test('reordering inside a full column is allowed', function () {
    $cardC = Task::create(['title' => 'Card C', 'status' => 'todo', 'order_position' => '131070.0000000000']);

    $moved = Livewire::test(TestBoard::class, ['todoLimit' => 2])
        ->instance()
        ->moveCard((string) $cardC->id, 'todo', null, (string) $this->cardA->id);

    expect($moved)->toBeTrue()
        ->and((float) $cardC->fresh()->order_position)->toBeLessThan((float) $this->cardA->fresh()->order_position);
});

test('a card moves between two cards of a board whose query left-joins another table', function () {
    $above = Task::create(['title' => 'Above', 'status' => 'in_progress', 'order_position' => '1000.0000000000']);
    $below = Task::create(['title' => 'Below', 'status' => 'in_progress', 'order_position' => '2000.0000000000']);
    DB::table('task_states')->insert(['task_id' => $above->id, 'state_value' => 'open']);

    Livewire::test(TestBoard::class, ['leftJoinState' => true])
        ->call('moveCard', (string) $this->cardA->id, 'in_progress', (string) $above->id, (string) $below->id)
        ->assertDispatched('kanban-card-moved');

    expect($this->cardA->fresh()->status)->toBe('in_progress')
        ->and((float) $this->cardA->fresh()->order_position)->toBeGreaterThan(1000.0)->toBeLessThan(2000.0);
});

test('a reference card outside the board query does not place the moved card', function () {
    $outside = Task::create(['title' => 'Outside', 'status' => 'in_progress', 'priority' => 'low', 'order_position' => '9000000.0000000000']);

    Livewire::test(TestBoard::class, ['onlyPriority' => 'medium'])
        ->call('moveCard', (string) $this->cardA->id, 'in_progress', (string) $outside->id)
        ->assertDispatched('kanban-card-moved');

    expect((float) $this->cardA->fresh()->order_position)->toBeLessThan(9000000.0);
});
