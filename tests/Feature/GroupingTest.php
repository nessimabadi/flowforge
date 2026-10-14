<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Flowforge\Grouping;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestGroupedBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestJoinedColumnBoard;
use Relaticle\Flowforge\Tests\Fixtures\User;

function groupedTask(string $title, string $status, string $priority, string $position): Task
{
    return Task::create(['title' => $title, 'status' => $status, 'priority' => $priority, 'order_position' => $position]);
}

function laneIds(array $laneData): array
{
    return array_column($laneData['lanes'], 'id');
}

function cellTitles(array $laneData, string $laneId, string $columnId): array
{
    $lane = collect($laneData['lanes'])->firstWhere('id', $laneId);

    return array_map(fn (array $record): string => $record['title'], $lane['cells'][$columnId]['items']);
}

test('attribute lanes come from the cards in view, busiest first', function () {
    groupedTask('Low one', 'todo', 'low', '65535.0000000000');
    groupedTask('Low two', 'in_progress', 'low', '65535.0000000000');
    groupedTask('High one', 'todo', 'high', '131070.0000000000');

    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();
    $lanes = $board->getLanes($board->getGrouping('priority'));

    expect(array_map(fn ($lane) => $lane->getName(), $lanes))->toBe(['low', 'high', Grouping::EMPTY_LANE])
        ->and((string) $lanes[0]->getLabel())->toBe('Low');
});

test('attribute lanes follow the board search', function () {
    groupedTask('Low one', 'todo', 'low', '65535.0000000000');
    groupedTask('High one', 'todo', 'high', '131070.0000000000');

    $board = Livewire::test(TestGroupedBoard::class)->set('tableSearch', 'High')->instance()->getBoard();

    expect(laneIds($board->getLaneViewData($board->getGrouping('priority'), 10, [])))->toBe(['high']);
});

test('each card lands in its own column cell', function () {
    groupedTask('Low todo', 'todo', 'low', '65535.0000000000');
    groupedTask('Low doing', 'in_progress', 'low', '65535.0000000000');

    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();
    $data = $board->getLaneViewData($board->getGrouping('priority'), 10, []);

    expect(cellTitles($data, 'low', 'todo'))->toBe(['Low todo'])
        ->and(cellTitles($data, 'low', 'in_progress'))->toBe(['Low doing']);
});

test('a lane whose cards all sit in hidden columns is not rendered', function () {
    groupedTask('Visible', 'todo', 'low', '65535.0000000000');
    groupedTask('Parked', 'archived', 'urgent', '65535.0000000000');

    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();

    expect(laneIds($board->getLaneViewData($board->getGrouping('priority'), 10, [])))->toBe(['low']);
});

test('the empty lane renders last', function () {
    $emma = User::factory()->create(['name' => 'Emma Collins']);
    groupedTask('Assigned', 'todo', 'low', '65535.0000000000')->assignees()->attach($emma);
    groupedTask('Unassigned', 'todo', 'low', '131070.0000000000');

    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();

    expect(laneIds($board->getLaneViewData($board->getGrouping('assignee'), 10, [])))
        ->toBe([(string) $emma->id, Grouping::EMPTY_LANE]);
});

test('a card with two assignees appears in both lanes', function () {
    $emma = User::factory()->create(['name' => 'Emma Collins']);
    $marcus = User::factory()->create(['name' => 'Marcus Johnson']);
    groupedTask('Shared', 'todo', 'low', '65535.0000000000')->assignees()->attach([$emma->id, $marcus->id]);

    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();
    $data = $board->getLaneViewData($board->getGrouping('assignee'), 10, []);

    expect(cellTitles($data, (string) $emma->id, 'todo'))->toBe(['Shared'])
        ->and(cellTitles($data, (string) $marcus->id, 'todo'))->toBe(['Shared']);
});

test('each cell is capped at the page size and still reports its full total', function () {
    groupedTask('First', 'todo', 'low', '65535.0000000000');
    groupedTask('Second', 'todo', 'low', '131070.0000000000');
    groupedTask('Third', 'todo', 'low', '196605.0000000000');

    $board = Livewire::test(TestGroupedBoard::class, ['cardsPerCell' => 2])->instance()->getBoard();
    $lane = $board->getLaneViewData($board->getGrouping('priority'), 10, [])['lanes'][0];

    expect($lane['cells']['todo']['items'])->toHaveCount(2)
        ->and($lane['cells']['todo']['total'])->toBe(3)
        ->and(array_column($lane['cells']['todo']['items'], 'title'))->toBe(['First', 'Second']);
});

test('a raised cell limit loads more cards for that cell only', function () {
    groupedTask('First', 'todo', 'low', '65535.0000000000');
    groupedTask('Second', 'todo', 'low', '131070.0000000000');

    $board = Livewire::test(TestGroupedBoard::class, ['cardsPerCell' => 1])->instance()->getBoard();
    $data = $board->getLaneViewData($board->getGrouping('priority'), 10, ['low' => ['todo' => 2]]);

    expect(cellTitles($data, 'low', 'todo'))->toBe(['First', 'Second']);
});

test('lanes beyond the visible count are reported, not loaded', function () {
    groupedTask('Low', 'todo', 'low', '65535.0000000000');
    groupedTask('High', 'todo', 'high', '131070.0000000000');

    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();
    $data = $board->getLaneViewData($board->getGrouping('priority'), 1, []);

    expect(laneIds($data))->toHaveCount(1)
        ->and($data['hasMoreLanes'])->toBeTrue();
});

test('each lane costs one record query, the empty lane included', function () {
    groupedTask('Low', 'todo', 'low', '65535.0000000000');
    groupedTask('Medium', 'todo', 'medium', '131070.0000000000');
    groupedTask('High', 'todo', 'high', '196605.0000000000');

    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();

    DB::enableQueryLog();
    $board->getLaneViewData($board->getGrouping('priority'), 10, []);
    $recordQueries = collect(DB::getQueryLog())->filter(fn (array $entry): bool => str_contains($entry['query'], 'flowforge_cell_row'));

    expect($recordQueries)->toHaveCount(4);
});

test('a board whose column lives on a joined table partitions by it', function () {
    $open = groupedTask('Open deal', 'todo', 'low', '65535.0000000000');
    $won = groupedTask('Won deal', 'todo', 'low', '131070.0000000000');
    DB::table('task_states')->insert([
        ['task_id' => $open->id, 'state_value' => 'open'],
        ['task_id' => $won->id, 'state_value' => 'won'],
    ]);

    $board = Livewire::test(TestJoinedColumnBoard::class)->instance()->getBoard();
    $data = $board->getLaneViewData($board->getGrouping('priority'), 10, []);

    expect(cellTitles($data, 'low', 'open'))->toBe(['Open deal'])
        ->and(cellTitles($data, 'low', 'won'))->toBe(['Won deal']);
});

test('an unknown grouping name resolves to none', function () {
    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();

    expect($board->getGrouping('nope'))->toBeNull()
        ->and($board->getGrouping(null))->toBeNull();
});

test('a lane query stops at the cell limit in SQL, per column', function () {
    groupedTask('First', 'todo', 'low', '65535.0000000000');
    groupedTask('Second', 'todo', 'low', '131070.0000000000');
    groupedTask('Third', 'todo', 'low', '196605.0000000000');

    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();
    $lane = $board->getLanes($board->getGrouping('priority'))[0];

    expect($board->getLaneRecords($lane, 2)->pluck('title')->all())->toBe(['First', 'Second']);
});

test('a raised limit on one cell leaves the other cell on the page size', function () {
    groupedTask('Todo one', 'todo', 'low', '65535.0000000000');
    groupedTask('Todo two', 'todo', 'low', '131070.0000000000');
    groupedTask('Doing one', 'in_progress', 'low', '65535.0000000000');
    groupedTask('Doing two', 'in_progress', 'low', '131070.0000000000');

    $board = Livewire::test(TestGroupedBoard::class, ['cardsPerCell' => 1])->instance()->getBoard();
    $data = $board->getLaneViewData($board->getGrouping('priority'), 10, ['low' => ['todo' => 2]]);

    expect(cellTitles($data, 'low', 'todo'))->toBe(['Todo one', 'Todo two'])
        ->and(cellTitles($data, 'low', 'in_progress'))->toBe(['Doing one']);
});

test('lane records keep the board query eager loads', function () {
    $emma = User::factory()->create(['name' => 'Emma Collins']);
    groupedTask('Assigned', 'todo', 'low', '65535.0000000000')->assignees()->attach($emma);

    $board = Livewire::test(TestGroupedBoard::class)->instance()->getBoard();
    $lane = $board->getLanes($board->getGrouping('priority'))[0];

    expect($board->getLaneRecords($lane, 5)->first()->relationLoaded('assignees'))->toBeTrue();
});
