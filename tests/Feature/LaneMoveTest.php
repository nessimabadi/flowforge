<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestGroupedBoard;
use Relaticle\Flowforge\Tests\Fixtures\User;

beforeEach(function () {
    $this->emma = User::factory()->create(['name' => 'Emma Collins']);
    $this->marcus = User::factory()->create(['name' => 'Marcus Johnson']);
    $this->chloe = User::factory()->create(['name' => 'Chloe Nguyen']);
    $this->task = Task::create([
        'title' => 'Proration preview',
        'status' => 'todo',
        'priority' => 'low',
        'created_by' => $this->emma->id,
        'order_position' => '65535.0000000000',
    ]);
    $this->urgent = Task::create([
        'title' => 'Refund dispute',
        'status' => 'todo',
        'priority' => 'high',
        'created_by' => $this->emma->id,
        'order_position' => '131070.0000000000',
    ]);
    $this->urgent->assignees()->attach($this->chloe->id);
});

function groupedBoard(string $groupBy, array $properties = []): TestGroupedBoard
{
    return Livewire::test(TestGroupedBoard::class, ['groupBy' => $groupBy, ...$properties])->instance();
}

test('moving a card into another priority lane writes the priority', function () {
    $moved = groupedBoard('priority')->moveCard((string) $this->task->id, 'todo', null, null, 'low', 'high');

    expect($moved)->toBeTrue()
        ->and($this->task->fresh()->priority)->toBe('high');
});

test('a move across a lane and a column writes both', function () {
    groupedBoard('priority')->moveCard((string) $this->task->id, 'in_progress', null, null, 'low', 'high');

    expect($this->task->fresh())
        ->status->toBe('in_progress')
        ->priority->toBe('high');
});

test('a read-only grouping refuses a move into another lane', function () {
    Livewire::test(TestGroupedBoard::class, ['groupBy' => 'creator'])
        ->call('moveCard', (string) $this->task->id, 'todo', null, null, (string) $this->emma->id, (string) $this->marcus->id)
        ->assertNotified(__('flowforge::flowforge.lanes_read_only', ['grouping' => 'Creator']));

    expect($this->task->fresh()->created_by)->toBe($this->emma->id);
});

test('a read-only grouping still moves cards between columns inside a lane', function () {
    $lane = (string) $this->emma->id;

    expect(groupedBoard('creator')->moveCard((string) $this->task->id, 'in_progress', null, null, $lane, $lane))->toBeTrue()
        ->and($this->task->fresh()->status)->toBe('in_progress');
});

test('the move callback receives the lane the card left', function () {
    $this->task->assignees()->attach([$this->emma->id, $this->marcus->id]);

    groupedBoard('assignee')->moveCard((string) $this->task->id, 'todo', null, null, (string) $this->marcus->id, (string) $this->chloe->id);

    expect($this->task->assignees()->pluck('users.id')->sort()->values()->all())
        ->toBe(collect([$this->emma->id, $this->chloe->id])->sort()->values()->all());
});

test('a lane that is no longer on the board is refused', function () {
    Livewire::test(TestGroupedBoard::class, ['groupBy' => 'priority'])
        ->call('moveCard', (string) $this->task->id, 'todo', null, null, 'low', 'ghost')
        ->assertNotified(__('flowforge::flowforge.unknown_lane'));

    expect($this->task->fresh()->priority)->toBe('low');
});

test('a card cannot leave a lane it is not in', function () {
    $this->task->assignees()->attach($this->emma->id);
    $this->urgent->assignees()->attach($this->marcus->id);

    Livewire::test(TestGroupedBoard::class, ['groupBy' => 'assignee'])
        ->call('moveCard', (string) $this->task->id, 'todo', null, null, (string) $this->marcus->id, (string) $this->chloe->id)
        ->assertNotified(__('flowforge::flowforge.unknown_lane'));

    expect($this->task->assignees()->pluck('users.id')->all())->toBe([$this->emma->id]);
});

test('a refused lane callback rolls back the column move', function () {
    Livewire::test(TestGroupedBoard::class, ['groupBy' => 'strict'])
        ->call('moveCard', (string) $this->task->id, 'in_progress', null, null, 'low', 'high')
        ->assertNotified('Nope');

    expect($this->task->fresh()->status)->toBe('todo');
});

test('a refused lane callback rolls back the column move in a limited column', function () {
    Livewire::test(TestGroupedBoard::class, ['groupBy' => 'strict', 'inProgressLimit' => 5])
        ->call('moveCard', (string) $this->task->id, 'in_progress', null, null, 'low', 'high')
        ->assertNotified('Nope');

    expect($this->task->fresh())
        ->status->toBe('todo')
        ->order_position->toEqual('65535.0000000000');
});

test('a limited column hands the lane callback the card it wrote', function () {
    groupedBoard('probe', ['inProgressLimit' => 5])->moveCard((string) $this->task->id, 'in_progress', null, null, 'low', 'high');

    expect($this->task->fresh())
        ->status->toBe('in_progress')
        ->title->toBe('in_progress');
});

test('a full limited column refuses a lane move before the callback runs', function () {
    Task::create(['title' => 'Occupant', 'status' => 'in_progress', 'priority' => 'low', 'order_position' => '65535.0000000000']);

    Livewire::test(TestGroupedBoard::class, ['groupBy' => 'priority', 'inProgressLimit' => 1])
        ->call('moveCard', (string) $this->task->id, 'in_progress', null, null, 'low', 'high')
        ->assertNotified(__('flowforge::flowforge.column_full', ['column' => 'In Progress', 'limit' => 1]));

    expect($this->task->fresh())
        ->status->toBe('todo')
        ->priority->toBe('low');
});

test('lane ids are ignored while the board is not grouped', function () {
    expect(groupedBoard('')->moveCard((string) $this->task->id, 'in_progress', null, null, 'a', 'b'))->toBeTrue();
});
