<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestEnumColumnBoard;

function enumBoardColumnIds(array $viewData): array
{
    $flat = collect($viewData['columns'])->flatMap(fn (array $column): array => $column['items']);
    $lanes = collect($viewData['lanes'])
        ->flatMap(fn (array $lane): array => $lane['cells'])
        ->flatMap(fn (array $cell): array => $cell['items']);

    return $flat->merge($lanes)->pluck('column')->all();
}

dataset('enum column boards', [
    'pure enum' => [true, 'Review', 'Shipped'],
    'backed enum' => [false, 'active', 'pending'],
]);

test('a board whose column is an enum renders and serializes string column ids', function (bool $pureEnum, string $first, string $second): void {
    Task::create(['title' => 'First card', 'status' => $first, 'priority' => 'low', 'order_position' => '65535.0000000000']);
    Task::create(['title' => 'Second card', 'status' => $second, 'priority' => 'low', 'order_position' => '65535.0000000000']);

    $component = Livewire::test(TestEnumColumnBoard::class, ['pureEnum' => $pureEnum])
        ->assertSee('First card')
        ->assertSee('Second card');

    $ids = enumBoardColumnIds($component->instance()->getBoard()->getViewData());

    expect($ids)->toEqualCanonicalizing([$first, $second])
        ->and(collect($ids)->every(fn (mixed $id): bool => is_string($id)))->toBeTrue();
})->with('enum column boards');

test('a grouped board whose column is an enum renders and serializes string column ids', function (bool $pureEnum, string $first, string $second): void {
    Task::create(['title' => 'First card', 'status' => $first, 'priority' => 'low', 'order_position' => '65535.0000000000']);
    Task::create(['title' => 'Second card', 'status' => $second, 'priority' => 'low', 'order_position' => '65535.0000000000']);

    $component = Livewire::test(TestEnumColumnBoard::class, ['pureEnum' => $pureEnum, 'groupBy' => 'priority'])
        ->assertSee('First card')
        ->assertSee('Second card');

    $ids = enumBoardColumnIds($component->instance()->getBoard()->getViewData());

    expect($ids)->toEqualCanonicalizing([$first, $second])
        ->and(collect($ids)->every(fn (mixed $id): bool => is_string($id)))->toBeTrue();
})->with('enum column boards');
