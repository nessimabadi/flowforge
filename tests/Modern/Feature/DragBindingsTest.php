<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestGroupedBoard;

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
        ->assertSeeHtml('x-bind:data-drop="columnDrop(\'todo\')"');
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
        ->assertSeeHtml("x-bind:data-drop=\"cellDrop('todo', 'low')\"");
});

test('collapsed rails keep their drag-expand binding', function () {
    Task::create(['title' => 'Only', 'status' => 'todo', 'order_position' => '65535.0000000000']);

    $html = Livewire::test(TestBoard::class, ['collapseEmpty' => true])->html();

    expect($html)->toMatch('/<div\s[^>]*data-collapsed[^>]*x-bind:data-drop="columnDrop\(\'in_progress\'\)"/');
});

test('numeric column ids are emitted as strings', function () {
    Task::create(['title' => 'Only', 'status' => '1', 'order_position' => '65535.0000000000']);

    $html = Livewire::test(DragBindingsNumericBoard::class, ['collapseEmpty' => true])->html();

    expect($html)
        ->toContain("columnDrop('2')")
        ->toContain("displayCount('2'")
        ->not->toContain('columnDrop(2)');
});

test('the add row on a numeric column carries the column as a string', function () {
    $html = Livewire::test(DragBindingsNumericBoard::class, ['withCreateAction' => true])->html();

    expect($html)
        ->toContain('\u0022column\u0022:\u00222\u0022')
        ->not->toContain('\u0022column\u0022:2');
});

test('the add row on a numeric column creates the card', function () {
    Livewire::test(DragBindingsNumericBoard::class, ['withCreateAction' => true])
        ->assertActionEnabled(TestAction::make('createTask')->arguments(['column' => '2']))
        ->callAction(TestAction::make('createTask')->arguments(['column' => '2']), ['title' => 'Numeric card']);

    expect(Task::where('title', 'Numeric card')->value('status'))->toBe('2');
});

test('the resting header count is server text a re-render can update', function () {
    Task::create(['title' => 'One', 'status' => 'todo', 'order_position' => '65535.0000000000']);
    Task::create(['title' => 'Two', 'status' => 'todo', 'order_position' => '131070.0000000000']);

    Livewire::test(TestBoard::class, ['todoLimit' => 3])
        ->assertSeeHtml('data-total="2"')
        ->assertSeeHtml('<span x-show="! dragSource">2/3</span>')
        ->assertSeeHtml("displayCount('todo', Number(\$el.parentElement.dataset.total))");
});
