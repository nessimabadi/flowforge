<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

test('every visible column gets an add row', function () {
    $component = Livewire::test(TestBoard::class, ['withCreateAction' => true]);
    $html = $component->html();

    expect(substr_count($html, 'ff-add-row'))->toBe(3)
        ->and($html)->toContain('Add ' . e($component->instance()->getBoard()->getCardLabel()));
});

test('a board without a create card action renders no add row', function () {
    Livewire::test(TestBoard::class)->assertDontSeeHtml('ff-add-row');
});

test('the add row creates the card in its own column', function () {
    Livewire::test(TestBoard::class, ['withCreateAction' => true])
        ->callAction(TestAction::make('createTask')->arguments(['column' => 'in_progress']), ['title' => 'Fresh card']);

    expect(Task::where('title', 'Fresh card')->value('status'))->toBe('in_progress');
});

test('creating into a column the board does not show is refused', function () {
    Livewire::test(TestBoard::class, ['withCreateAction' => true])
        ->assertActionDisabled(TestAction::make('createTask')->arguments(['column' => 'archived']))
        ->callAction(TestAction::make('createTask')->arguments(['column' => 'archived']), ['title' => 'Sneaky']);

    expect(Task::where('title', 'Sneaky')->exists())->toBeFalse();
});

test('an action the app hides renders no add row and no header button', function () {
    $html = Livewire::test(TestBoard::class, ['withCreateAction' => true, 'hideCreateAction' => true])->html();

    expect($html)->not->toContain('ff-add-row')
        ->and($html)->not->toContain('mountAction(\'createTask\'');
});

test('creating without a column is refused', function () {
    Livewire::test(TestBoard::class, ['withCreateAction' => true])
        ->assertActionDisabled('createTask')
        ->callAction('createTask', ['title' => 'Columnless']);

    expect(Task::where('title', 'Columnless')->exists())->toBeFalse();
});

test('the add row mounts the create action with its own column', function () {
    $html = Livewire::test(TestBoard::class, ['withCreateAction' => true])->html();

    expect($html)->toMatch(createCardPayloadPattern('ff-add-row', 'in_progress'));
});

test('the header button mounts the create action with its own column', function () {
    $html = Livewire::test(TestBoard::class, ['withCreateAction' => true])->html();

    expect($html)->toMatch(createCardPayloadPattern('fi-icon-btn', 'in_progress'));
});

function createCardPayloadPattern(string $class, string $column): string
{
    return '/' . preg_quote($class, '/') . '[^>]*wire:click="mountAction\(\'createTask\', JSON\.parse\(\'\{\\\\u0022column\\\\u0022:\\\\u0022' . $column . '\\\\u0022\}\'\)\)"/s';
}
