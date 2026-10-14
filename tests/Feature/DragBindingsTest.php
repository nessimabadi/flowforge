<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestGroupedBoard;

class DragBindingsNumericBoard extends TestBoard
{
    public function board(Board $board): Board
    {
        return parent::board($board)->columns([
            Column::make('1')->label('First'),
            Column::make('2')->label('Second'),
        ]);
    }
}

test('a column root binds its hover and blocked states', function () {
    Livewire::test(TestBoard::class)
        ->assertSeeHtml('ff-column--active')
        ->assertSeeHtml("isColumnBlocked('todo')");
});

test('the header count re-renders while a card hovers', function () {
    Livewire::test(TestBoard::class)->assertSeeHtml("displayCount('todo'");
});

test('native dragenter hooks are gone', function () {
    Livewire::test(TestBoard::class)->assertDontSeeHtml('dragenter');
});

test('each sortable container configures itself for fallback dragging', function () {
    Livewire::test(TestBoard::class)->assertSeeHtml('configureSortable($el)');
});

test('a limited column carries the limit hint', function () {
    Task::create(['title' => 'Only', 'status' => 'todo', 'order_position' => '65535.0000000000']);

    Livewire::test(TestBoard::class, ['todoLimit' => 1])->assertSeeHtml('ff-limit-hint');
});

test('lane cells bind their own hover state', function () {
    Task::create(['title' => 'Low card', 'status' => 'todo', 'priority' => 'low', 'order_position' => '65535.0000000000']);

    Livewire::test(TestGroupedBoard::class)
        ->set('groupBy', 'priority')
        ->assertSeeHtml("isCellBlocked('todo', 'low')");
});

test('collapsed rails keep their drag-expand binding', function () {
    Task::create(['title' => 'Only', 'status' => 'todo', 'order_position' => '65535.0000000000']);

    Livewire::test(TestBoard::class, ['collapseEmpty' => true])
        ->assertSeeHtml("'w-[300px] min-w-[300px]': dragOverColumn ===");
});

test('numeric column ids are emitted as strings', function () {
    Task::create(['title' => 'Only', 'status' => '1', 'order_position' => '65535.0000000000']);

    $html = Livewire::test(DragBindingsNumericBoard::class, ['collapseEmpty' => true])->html();

    expect($html)
        ->toContain("isColumnBlocked('2')")
        ->toContain("displayCount('2'")
        ->toContain('dragOverColumn === &quot;2&quot;')
        ->not->toContain('dragOverColumn === 2');
});
