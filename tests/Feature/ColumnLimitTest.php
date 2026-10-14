<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

function todoCard(string $title, string $position): Task
{
    return Task::create(['title' => $title, 'status' => 'todo', 'order_position' => $position]);
}

function addRowButton(string $html, string $column): string
{
    preg_match_all('/<button[^>]*ff-add-row[^>]*>/s', $html, $matches);

    foreach ($matches[0] as $button) {
        if (preg_match('/u0022' . preg_quote($column, '/') . '\\\\u0022/', $button)) {
            return $button;
        }
    }

    return '';
}

beforeEach(function () {
    todoCard('Alpha', '65535.0000000000');
    todoCard('Beta', '131070.0000000000');
});

test('the header shows the count against the limit', function () {
    Livewire::test(TestBoard::class, ['todoLimit' => 3])->assertSeeHtml('>2/3</span>');
});

test('the limit counts cards that a search hides', function () {
    Livewire::test(TestBoard::class, ['todoLimit' => 3])
        ->set('tableSearch', 'Alpha')
        ->assertSeeHtml('>2/3</span>');
});

test('a column at its limit is marked full', function () {
    Livewire::test(TestBoard::class, ['todoLimit' => 2])->assertSeeHtml('ff-count--full');
});

test('a column below its limit is not marked full', function () {
    Livewire::test(TestBoard::class, ['todoLimit' => 3])->assertDontSeeHtml('ff-count--full');
});

test('the column root exposes its limit for the drag script', function () {
    Livewire::test(TestBoard::class, ['todoLimit' => 3])
        ->assertSeeHtml('data-column-limit="3"')
        ->assertSeeHtml('data-column-total="2"');
});

test('a full column refuses new cards', function () {
    $component = Livewire::test(TestBoard::class, ['todoLimit' => 2, 'withCreateAction' => true]);
    $todoRow = addRowButton($component->html(), 'todo');

    expect($todoRow)->toMatch('/\sdisabled\s/')
        ->and($todoRow)->toContain('title="' . __('flowforge::flowforge.limit_reached') . '"')
        ->and(addRowButton($component->html(), 'in_progress'))->not->toMatch('/\sdisabled\s/');

    $component
        ->assertActionDisabled(TestAction::make('createTask')->arguments(['column' => 'todo']))
        ->callAction(TestAction::make('createTask')->arguments(['column' => 'todo']), ['title' => 'Overflow']);

    expect(Task::where('title', 'Overflow')->exists())->toBeFalse();
});

test('a column below its limit accepts new cards', function () {
    $component = Livewire::test(TestBoard::class, ['todoLimit' => 3, 'withCreateAction' => true]);

    expect(addRowButton($component->html(), 'todo'))->not->toMatch('/\sdisabled\s/');

    $component
        ->assertActionEnabled(TestAction::make('createTask')->arguments(['column' => 'todo']))
        ->callAction(TestAction::make('createTask')->arguments(['column' => 'todo']), ['title' => 'Third']);

    expect(Task::where('title', 'Third')->value('status'))->toBe('todo');
});
