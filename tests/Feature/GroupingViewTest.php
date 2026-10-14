<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestGroupedBoard;
use Relaticle\Flowforge\Tests\Fixtures\User;

function viewTask(string $title, string $priority, string $position, string $status = 'todo'): Task
{
    return Task::create(['title' => $title, 'status' => $status, 'priority' => $priority, 'order_position' => $position]);
}

test('a board without groupings shows no picker', function () {
    Livewire::test(TestBoard::class)->assertDontSeeHtml('flowforge-group-by');
});

test('the picker lists every grouping', function () {
    Livewire::test(TestGroupedBoard::class)
        ->assertSeeHtml('>' . __('flowforge::flowforge.group_by') . '</label>')
        ->assertSeeHtml('value="priority"')
        ->assertSeeHtml('value="assignee"');
});

test('choosing a grouping renders a lane per value', function () {
    viewTask('Low card', 'low', '65535.0000000000');
    viewTask('High card', 'high', '131070.0000000000');

    Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'priority')
        ->assertSeeHtml('data-lane-id="low"')
        ->assertSeeHtml('data-lane-id="high"')
        ->assertSeeHtmlInOrder(['data-lane-id="low"', 'Low card'])
        ->assertSeeHtmlInOrder(['data-lane-id="high"', 'High card']);
});

test('lanes label themselves and show their total', function () {
    viewTask('Low card', 'low', '65535.0000000000');

    Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'priority')
        ->assertSeeHtml('>Low</h4>');
});

test('the grouping is read from the url', function () {
    viewTask('Low card', 'low', '65535.0000000000');

    Livewire::withQueryParams(['groupBy' => 'priority'])
        ->test(TestGroupedBoard::class)
        ->assertSeeHtml('data-lane-id="low"');
});

test('an unknown grouping in the url falls back to the plain board', function () {
    viewTask('Low card', 'low', '65535.0000000000');

    Livewire::withQueryParams(['groupBy' => 'nope'])
        ->test(TestGroupedBoard::class)
        ->assertOk()
        ->assertDontSeeHtml('data-lane-id')
        ->assertSeeHtml('data-column-id="todo"')
        ->assertSeeHtml('wire:key="card-board-');
});

test('more lanes load on request', function () {
    viewTask('Low one', 'low', '65535.0000000000');
    viewTask('Low two', 'low', '131070.0000000000');
    viewTask('High one', 'high', '196605.0000000000');

    Livewire::test(TestGroupedBoard::class, ['lanesPerPage' => 1])
        ->set('groupBy', 'priority')
        ->assertSee(__('flowforge::flowforge.show_more_lanes'))
        ->assertDontSeeHtml('data-lane-id="high"')
        ->call('loadMoreLanes')
        ->assertSeeHtml('data-lane-id="high"');
});

test('more cards load for one cell', function () {
    viewTask('First', 'low', '65535.0000000000');
    viewTask('Second', 'low', '131070.0000000000');

    Livewire::test(TestGroupedBoard::class, ['cardsPerCell' => 1])
        ->set('groupBy', 'priority')
        ->assertSee(__('flowforge::flowforge.show_more_cards', ['count' => 1]))
        ->assertSeeHtmlInOrder(['data-lane-id="low"', 'First'])
        ->assertDontSeeHtml('wire:key="card-low-' . Task::where('title', 'Second')->value('id') . '"')
        ->call('loadMoreItems', 'todo', null, 'low')
        ->assertSeeHtml('wire:key="card-low-' . Task::where('title', 'Second')->value('id') . '"');
});

test('switching the grouping resets lane and cell paging', function () {
    Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'priority')
        ->call('loadMoreLanes')
        ->call('loadMoreItems', 'todo', null, 'low')
        ->set('groupBy', 'assignee')
        ->assertSet('visibleLaneCount', null)
        ->assertSet('cellCardLimits', []);
});

test('a card in two lanes renders once in each', function () {
    $emma = User::factory()->create(['name' => 'Emma Collins']);
    $marcus = User::factory()->create(['name' => 'Marcus Johnson']);
    $task = viewTask('Shared card', 'low', '65535.0000000000');
    $task->assignees()->attach([$emma->id, $marcus->id]);

    Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'assignee')
        ->assertSeeHtml('>Emma Collins</h4>')
        ->assertSeeHtml('>Marcus Johnson</h4>')
        ->assertSeeHtml('wire:key="card-' . $emma->id . '-' . $task->id . '"')
        ->assertSeeHtml('wire:key="card-' . $marcus->id . '-' . $task->id . '"');
});

test('lane mode puts one header per column above the lanes', function () {
    viewTask('Low card', 'low', '65535.0000000000');
    viewTask('High card', 'high', '131070.0000000000');

    Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'priority')
        ->assertSeeHtmlInOrder(['data-column-root="todo"', 'data-column-root="in_progress"', 'data-lane-id="low"']);
});

test('the board root says whether cards may cross lanes', function () {
    Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'priority')
        ->assertSeeHtml('data-lanes-writable="true"')
        ->set('groupBy', 'creator')
        ->assertSeeHtml('data-lanes-writable="false"');
});
