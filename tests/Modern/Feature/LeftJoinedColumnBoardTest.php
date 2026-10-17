<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestLeftJoinedColumnBoard;

pest()->group('pgsql');

function joinedColumnCard(string $title, ?string $state, string $position): Task
{
    $task = Task::create(['title' => $title, 'status' => 'todo', 'order_position' => $position]);

    if ($state !== null) {
        DB::table('task_states')->insert(['task_id' => $task->id, 'state_value' => $state]);
    }

    return $task;
}

function joinedColumnState(Task $task): ?string
{
    return DB::table('task_states')->where('task_id', $task->id)->value('state_value');
}

test('a board whose column comes from a left join renders the cards in their columns', function () {
    joinedColumnCard('Open card', 'open', '65535.0000000000');
    joinedColumnCard('Won card', 'won', '131070.0000000000');
    joinedColumnCard('Stateless card', null, '196605.0000000000');

    $columns = Livewire::test(TestLeftJoinedColumnBoard::class)
        ->assertSee('Open card')
        ->assertSee('Won card')
        ->assertDontSee('Stateless card')
        ->instance()->getBoard()->getViewData()['columns'];

    expect($columns['open']['total'])->toBe(1)
        ->and($columns['won']['total'])->toBe(1);
});

test('a card moves between two cards of another column', function () {
    $card = joinedColumnCard('Moving', 'open', '65535.0000000000');
    $above = joinedColumnCard('Above', 'won', '1000.0000000000');
    $below = joinedColumnCard('Below', 'won', '2000.0000000000');

    Livewire::test(TestLeftJoinedColumnBoard::class)
        ->call('moveCard', (string) $card->id, 'won', (string) $above->id, (string) $below->id)
        ->assertDispatched('kanban-card-moved');

    expect(joinedColumnState($card))->toBe('won')
        ->and((float) $card->fresh()->order_position)->toBeGreaterThan(1000.0)->toBeLessThan(2000.0);
});

test('a card without a joined row moves into a column', function () {
    $card = joinedColumnCard('Stateless', null, '196605.0000000000');
    $existing = joinedColumnCard('Existing', 'open', '1000.0000000000');

    Livewire::test(TestLeftJoinedColumnBoard::class)
        ->call('moveCard', (string) $card->id, 'open', (string) $existing->id)
        ->assertDispatched('kanban-card-moved');

    expect(joinedColumnState($card))->toBe('open')
        ->and((float) $card->fresh()->order_position)->toBeGreaterThan(1000.0);
});
