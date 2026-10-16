<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard;

beforeEach(function () {
    Task::create(['title' => 'Alpha', 'status' => 'todo', 'order_position' => '65535.0000000000']);
    Task::create(['title' => 'Beta', 'status' => 'todo', 'order_position' => '131070.0000000000']);
});

test('each column header shows its summary', function () {
    Livewire::test(TestBoard::class, ['withSummary' => true])
        ->assertSeeHtml('ff-muted truncate text-sm">2 shown</span>')
        ->assertSeeHtml('ff-muted truncate text-sm">0 shown</span>');
});

test('the summary follows the board search', function () {
    Livewire::test(TestBoard::class, ['withSummary' => true])
        ->set('tableSearch', 'Alpha')
        ->assertSeeHtml('ff-muted truncate text-sm">1 shown</span>')
        ->assertDontSeeHtml('ff-muted truncate text-sm">2 shown</span>');
});

test('a summary that returns null renders nothing', function () {
    Livewire::test(TestBoard::class, ['nullSummary' => true])
        ->assertDontSeeHtml('ff-column-summary');
});

test('a board without a summary renders nothing', function () {
    Livewire::test(TestBoard::class)->assertDontSeeHtml('ff-column-summary');
});

test('a summary closure receives the query by name and the column by type', function () {
    Livewire::test(TestBoard::class, ['namedSummary' => true])
        ->assertSeeHtml('ff-muted truncate text-sm">todo:2</span>')
        ->assertSeeHtml('ff-muted truncate text-sm">in_progress:0</span>');
});

test('a collapsed column renders no summary and an expanded one does', function () {
    Livewire::test(TestBoard::class, ['withSummary' => true, 'collapseEmpty' => true])
        ->assertSeeHtml('ff-muted truncate text-sm">2 shown</span>')
        ->assertDontSeeHtml('ff-muted truncate text-sm">0 shown</span>');
});
