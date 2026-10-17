<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoardResourcePage;

test('repeater add action inside a card action keeps the card action mounted', function () {
    $task = Task::factory()->create(['status' => 'todo']);

    $component = Livewire::test(TestBoardResourcePage::class)
        ->call('mountAction', 'editTask', [], ['recordKey' => $task->getKey()]);

    expect($component->get('mountedActions'))->toHaveCount(1);

    $itemsBefore = count($component->get('mountedActions.0.data.items'));

    $component->call('mountAction', 'add', [], [
        'recordKey' => $task->getKey(),
        'schemaComponent' => 'mountedActionSchema0.items',
    ]);

    expect($component->get('mountedActions'))->toHaveCount(1)
        ->and($component->get('mountedActions.0.name'))->toBe('editTask')
        ->and($component->get('mountedActions.0.data.items'))->toHaveCount($itemsBefore + 1);
});

test('repeater delete action inside a card action keeps the card action mounted', function () {
    $task = Task::factory()->create(['status' => 'todo']);

    $component = Livewire::test(TestBoardResourcePage::class)
        ->call('mountAction', 'editTask', [], ['recordKey' => $task->getKey()]);

    $items = $component->get('mountedActions.0.data.items');
    $itemKey = array_key_first($items);

    $component->call('mountAction', 'delete', ['item' => $itemKey], [
        'recordKey' => $task->getKey(),
        'schemaComponent' => 'mountedActionSchema0.items',
    ]);

    expect($component->get('mountedActions'))->toHaveCount(1)
        ->and($component->get('mountedActions.0.name'))->toBe('editTask')
        ->and($component->get('mountedActions.0.data.items'))->toHaveCount(count($items) - 1);
});

test('card actions stay mountable after a nested schema component action runs', function () {
    $task = Task::factory()->create(['status' => 'todo']);

    $component = Livewire::test(TestBoardResourcePage::class)
        ->call('mountAction', 'editTask', [], ['recordKey' => $task->getKey()])
        ->call('mountAction', 'add', [], [
            'recordKey' => $task->getKey(),
            'schemaComponent' => 'mountedActionSchema0.items',
        ])
        ->call('unmountAction')
        ->call('mountAction', 'editTask', [], ['recordKey' => $task->getKey()]);

    expect($component->get('mountedActions'))->toHaveCount(1)
        ->and($component->get('mountedActions.0.name'))->toBe('editTask');
});

test('modal actions registered on a card action still resolve through the board resolver', function () {
    $task = Task::factory()->create(['status' => 'todo']);

    $component = Livewire::test(TestBoardResourcePage::class)
        ->call('mountAction', 'editTask', [], ['recordKey' => $task->getKey()])
        ->call('mountAction', 'nestedModalAction', [], ['recordKey' => $task->getKey()]);

    expect($component->get('mountedActions'))->toHaveCount(2)
        ->and(array_column($component->get('mountedActions'), 'name'))
        ->toBe(['editTask', 'nestedModalAction']);
});

test('card action still resolves its record', function () {
    $task = Task::factory()->create(['status' => 'todo', 'title' => 'Resolve me']);

    $component = Livewire::test(TestBoardResourcePage::class)
        ->call('mountAction', 'editTask', [], ['recordKey' => $task->getKey()]);

    expect($component->instance()->getMountedAction()->getRecord()?->getKey())->toBe($task->getKey());
});
