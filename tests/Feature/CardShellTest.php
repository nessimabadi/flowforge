<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoardResourcePage;

beforeEach(function () {
    Task::create(['title' => 'Shell card', 'status' => 'todo', 'order_position' => '65535.0000000000']);
});

test('a card renders on the ring shell instead of a border', function () {
    $html = Livewire::test(TestBoardResourcePage::class)->html();

    expect($html)->toContain('ff-card')
        ->and($html)->not->toContain('border border-gray-200');
});

test('record actions sit in a corner revealed on hover and focus', function () {
    Livewire::test(TestBoardResourcePage::class)
        ->assertSeeHtml('group-hover:opacity-100')
        ->assertSeeHtml('focus-within:opacity-100');
});

test('each card carries a wire key scoped to the flat board', function () {
    $task = Task::first();

    Livewire::test(TestBoardResourcePage::class)
        ->assertSeeHtml('wire:key="card-board-' . $task->id . '"');
});
