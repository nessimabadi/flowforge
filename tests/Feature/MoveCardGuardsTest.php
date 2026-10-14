<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

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
