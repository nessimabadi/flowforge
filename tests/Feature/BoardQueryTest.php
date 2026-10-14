<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\StatusEnum;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    Task::create(['title' => 'Alpha', 'status' => 'todo', 'order_position' => '65535.0000000000']);
    Task::create(['title' => 'Beta', 'status' => 'todo', 'order_position' => '131070.0000000000']);
    Task::create(['title' => 'Gamma', 'status' => 'in_progress', 'order_position' => '65535.0000000000']);
});

test('the filtered column query applies the board search', function () {
    $board = Livewire::test(TestBoard::class)
        ->set('tableSearch', 'Alpha')
        ->instance()
        ->getBoard();

    expect($board->getFilteredColumnQuery('todo')->pluck('title')->all())->toBe(['Alpha']);
});

test('the filtered board query spans every column', function () {
    $board = Livewire::test(TestBoard::class)->instance()->getBoard();

    expect($board->getFilteredBoardQuery()->count())->toBe(3);
});

test('the unfiltered column count ignores the board search', function () {
    $board = Livewire::test(TestBoard::class)
        ->set('tableSearch', 'Alpha')
        ->instance()
        ->getBoard();

    expect($board->getUnfilteredColumnRecordCount('todo'))->toBe(2);
});

test('the record column id reads a backed enum as its value', function () {
    $board = Livewire::test(TestBoard::class)->instance()->getBoard();

    $record = new class extends Task
    {
        protected $casts = ['status' => StatusEnum::class];
    };
    $record->setRawAttributes(['status' => 'active']);

    expect($board->getRecordColumnId($record))->toBe('active');
});

test('the record column id reads a plain string', function () {
    $board = Livewire::test(TestBoard::class)->instance()->getBoard();

    expect($board->getRecordColumnId(Task::where('title', 'Gamma')->first()))->toBe('in_progress');
});
