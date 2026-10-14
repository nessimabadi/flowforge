<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestCardComponentsBoard;
use Relaticle\Flowforge\Tests\Fixtures\User;

function cardTask(array $attributes = []): Task
{
    return Task::create([
        'title' => 'Proration preview',
        'status' => 'todo',
        'priority' => 'high',
        'order_position' => '65535.0000000000',
        ...$attributes,
    ]);
}

function person(string $name): User
{
    return User::factory()->create(['name' => $name]);
}

test('a card title component replaces the default title', function () {
    cardTask();

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect(substr_count($html, '<h4'))->toBe(1);
});

test('a card tag renders a colored dot and its formatted label', function () {
    cardTask();

    Livewire::test(TestCardComponentsBoard::class)
        ->assertSeeHtml('ff-card-tag')
        ->assertSee('High');
});

test('card meta wraps its entries in the muted meta row', function () {
    cardTask();

    Livewire::test(TestCardComponentsBoard::class)->assertSeeHtml('ff-card-meta');
});

test('a card avatar falls back to initials and lists every name', function () {
    cardTask()->assignees()->attach(person('Emma Collins'));

    Livewire::test(TestCardComponentsBoard::class)
        ->assertSee('EC')
        ->assertSeeHtml('title="Emma Collins"');
});

test('a card avatar stack caps at its limit and counts the rest', function () {
    $task = cardTask();
    $task->assignees()->attach([
        person('Emma Collins')->id,
        person('Marcus Johnson')->id,
        person('Chloe Nguyen')->id,
        person('Daniel Kim')->id,
    ]);

    Livewire::test(TestCardComponentsBoard::class)
        ->assertSee('+1')
        ->assertSeeHtml('title="Emma Collins, Marcus Johnson, Chloe Nguyen, Daniel Kim"');
});

test('a card with no assignees renders no avatar markup', function () {
    cardTask();

    Livewire::test(TestCardComponentsBoard::class)->assertDontSeeHtml('ff-card-avatars');
});
