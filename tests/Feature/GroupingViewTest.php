<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestGroupedBoard;
use Relaticle\Flowforge\Tests\Fixtures\User;

function viewTask(string $title, string $priority, string $position, string $status = 'todo'): Task
{
    return Task::create(['title' => $title, 'status' => $status, 'priority' => $priority, 'order_position' => $position]);
}

function laneSegment(string $html, string $laneId): string
{
    $start = strpos($html, 'wire:key="lane-' . $laneId . '"');
    $next = strpos($html, 'wire:key="lane-', $start + 1);

    return substr($html, $start, $next === false ? null : $next - $start);
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

    $component = Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'priority')
        ->assertSeeHtml('data-lane-id="low"')
        ->assertSeeHtml('data-lane-id="high"');

    $html = $component->html();

    expect(laneSegment($html, 'low'))->toContain('Low card')->not->toContain('High card')
        ->and(laneSegment($html, 'high'))->toContain('High card')->not->toContain('Low card');
});

test('lanes label themselves and show their total', function () {
    viewTask('Low one', 'low', '65535.0000000000');
    viewTask('Low two', 'low', '131070.0000000000', 'in_progress');
    viewTask('High one', 'high', '196605.0000000000');

    $html = Livewire::test(TestGroupedBoard::class)->set('groupBy', 'priority')->html();

    expect($html)
        ->toMatch('/>Low<\/h4>\s*<span class="ff-muted text-sm tabular-nums">2<\/span>/')
        ->toMatch('/>High<\/h4>\s*<span class="ff-muted text-sm tabular-nums">1<\/span>/');
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
        ->assertSeeHtml('wire:key="card-low-' . Task::where('title', 'First')->value('id') . '"')
        ->assertDontSeeHtml('wire:key="card-low-' . Task::where('title', 'Second')->value('id') . '"')
        ->call('loadMoreItems', 'todo', null, 'low')
        ->assertSeeHtml('wire:key="card-low-' . Task::where('title', 'Second')->value('id') . '"');
});

test('switching the grouping resets lane and cell paging', function () {
    Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'priority')
        ->call('loadMoreLanes')
        ->call('loadMoreItems', 'todo', null, 'low')
        ->assertSet('visibleLaneCount', 20)
        ->assertSet('cellCardLimits.low.todo', 40)
        ->set('groupBy', 'assignee')
        ->assertSet('visibleLaneCount', null)
        ->assertSet('cellCardLimits', []);
});

test('a card in two lanes renders once in each', function () {
    $emma = User::factory()->create(['name' => 'Emma Collins']);
    $marcus = User::factory()->create(['name' => 'Marcus Johnson']);
    $task = viewTask('Shared card', 'low', '65535.0000000000');
    $task->assignees()->attach([$emma->id, $marcus->id]);

    $html = Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'assignee')
        ->assertSeeHtml('>Emma Collins</h4>')
        ->assertSeeHtml('>Marcus Johnson</h4>')
        ->html();

    expect(substr_count($html, 'wire:key="card-' . $emma->id . '-' . $task->id . '"'))->toBe(1)
        ->and(substr_count($html, 'wire:key="card-' . $marcus->id . '-' . $task->id . '"'))->toBe(1);
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

test('every part of the lane grid is keyed', function () {
    viewTask('Low card', 'low', '65535.0000000000');

    Livewire::test(TestGroupedBoard::class, ['cardsPerCell' => 1, 'lanesPerPage' => 1])
        ->set('groupBy', 'priority')
        ->assertSeeHtml('wire:key="lane-headers"')
        ->assertSeeHtml('wire:key="lane-header-todo"')
        ->assertSeeHtml('wire:key="lane-header-in_progress"')
        ->assertSeeHtml('wire:key="lane-low"')
        ->assertSeeHtml('wire:key="cell-low-todo"')
        ->assertSeeHtml('wire:key="cell-low-in_progress"');
});

test('the show more buttons are keyed', function () {
    viewTask('One', 'low', '65535.0000000000');
    viewTask('Two', 'low', '131070.0000000000');
    viewTask('Three', 'high', '196605.0000000000');

    Livewire::test(TestGroupedBoard::class, ['cardsPerCell' => 1, 'lanesPerPage' => 1])
        ->set('groupBy', 'priority')
        ->assertSeeHtml('wire:key="more-cards-low-todo"')
        ->assertSeeHtml('wire:key="more-lanes"');
});

test('the lane grid scrolls inside a bounded height with a board colored sticky header', function () {
    viewTask('Low card', 'low', '65535.0000000000');

    Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'priority')
        ->assertSeeHtml('max-height: calc(100vh - 13rem)')
        ->assertSeeHtml('background-color: var(--ff-board-bg)');
});

test('a client cannot overflow or inflate lane paging', function () {
    viewTask('Low card', 'low', '65535.0000000000');

    Livewire::test(TestGroupedBoard::class, ['cardsPerCell' => 5])
        ->set('groupBy', 'priority')
        ->call('loadMoreItems', 'todo', PHP_INT_MAX, 'low')
        ->assertSet('cellCardLimits.low.todo', 10)
        ->call('loadMoreItems', 'todo', PHP_INT_MAX, 'low')
        ->assertOk()
        ->assertSet('cellCardLimits.low.todo', 15)
        ->call('loadMoreItems', 'todo', -50, 'low')
        ->assertSet('cellCardLimits.low.todo', 16);
});

test('a client cannot inflate flat column paging', function () {
    foreach (range(1, 10) as $index) {
        viewTask("Card {$index}", 'low', (string) ($index * 65535) . '.0000000000');
    }

    Livewire::test(TestGroupedBoard::class, ['cardsPerCell' => 2])
        ->call('loadMoreItems', 'todo', PHP_INT_MAX)
        ->assertSet('columnCardLimits.todo', 4);
});

test('a client cannot write paging state', function (string $property, mixed $value) {
    expect(fn () => Livewire::test(TestGroupedBoard::class)->set($property, $value))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    ['visibleLaneCount', 1000000],
    ['cellCardLimits', ['low' => ['todo' => 1000000]]],
    ['columnCardLimits', ['todo' => 1000000]],
]);

test('the picker select has an id of its own per board', function () {
    $component = Livewire::test(TestGroupedBoard::class);

    $component->assertSeeHtml('id="flowforge-group-by-' . $component->id() . '"');
});

test('the picker sits in the board body without a header toolbar', function () {
    $html = Livewire::test(TestGroupedBoard::class)->html();

    expect(substr_count($html, 'id="flowforge-group-by-'))->toBe(1)
        ->and(strpos($html, 'id="flowforge-group-by-'))->toBeGreaterThan(strpos($html, 'ff-board'));
});

test('the picker moves to the page header with a header toolbar', function () {
    $html = Livewire::test(TestGroupedBoard::class, ['withHeaderToolbar' => true])->html();

    expect(substr_count($html, 'id="flowforge-group-by-'))->toBe(1)
        ->and(strpos($html, 'id="flowforge-group-by-'))->toBeLessThan(strpos($html, 'ff-board'));
});

test('lane mode runs no flat per column record query', function () {
    viewTask('Low card', 'low', '65535.0000000000');

    $flat = Livewire::test(TestGroupedBoard::class);
    $lanes = Livewire::test(TestGroupedBoard::class)->set('groupBy', 'priority');

    $flatQueries = function () use ($flat): array {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $flat->call('$refresh');
        DB::disableQueryLog();

        return array_column(DB::getQueryLog(), 'query');
    };

    $laneQueries = function () use ($lanes): array {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $lanes->call('$refresh');
        DB::disableQueryLog();

        return array_column(DB::getQueryLog(), 'query');
    };

    $isFlatRecordQuery = fn (string $query): bool => str_starts_with($query, 'select * from "tasks"');

    expect(array_filter($flatQueries(), $isFlatRecordQuery))->not->toBeEmpty()
        ->and(array_filter($laneQueries(), $isFlatRecordQuery))->toBeEmpty();
});

test('collapsing empty columns applies to the flat board only', function () {
    viewTask('Low card', 'low', '65535.0000000000');

    Livewire::test(TestGroupedBoard::class, ['collapseEmpty' => true])
        ->assertSeeHtml('flowforge-column-collapsed')
        ->set('groupBy', 'priority')
        ->assertDontSeeHtml('flowforge-column-collapsed');
});
