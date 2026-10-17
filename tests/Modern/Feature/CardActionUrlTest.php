<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardActionBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardActionConfirmBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardActionDefaultUrlBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardActionHiddenBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardActionHiddenModalBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardActionModalBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardActionModalHeadingBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardActionNewTabBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardActionTitleBoard;

beforeEach(function () {
    $this->task = Task::factory()->create(['status' => 'todo', 'title' => 'Linked task']);
});

function modernCardTitleHtml(string $html): string
{
    return str($html)
        ->after('ff-card-body')
        ->before('</h4>')
        ->toString();
}

test('card action with a url renders the card title as a link', function () {
    $title = modernCardTitleHtml(Livewire::test(TestCardActionBoard::class)->html());

    expect($title)->toContain('href="https://example.test/tasks/' . $this->task->getKey() . '"');
});

test('card action with a url does not mount an action on click', function () {
    $html = Livewire::test(TestCardActionBoard::class)->html();

    expect($html)->not->toContain("mountAction('view'");
});

test('card action honours openUrlInNewTab', function () {
    $html = Livewire::test(TestCardActionNewTabBoard::class)->html();

    expect(modernCardTitleHtml($html))
        ->toContain('href="https://example.test/tasks/' . $this->task->getKey() . '" target="_blank"')
        ->and($html)->not->toContain("mountAction('viewInNewTab'");
});

test('card action without a url still mounts the action', function () {
    $title = modernCardTitleHtml(Livewire::test(TestCardActionModalBoard::class)->html());

    expect($title)->toContain("mountAction('edit'")
        ->and($title)->not->toContain('href=');
});

test('a url card action with a card title schema renders one link', function () {
    $content = modernCardTitleHtml(Livewire::test(TestCardActionTitleBoard::class)->html());

    expect(substr_count($content, '<a '))->toBe(1)
        ->and($content)->toContain('Linked task');
});

test('a hidden url card action renders no link', function () {
    $content = modernCardTitleHtml(Livewire::test(TestCardActionHiddenBoard::class)->html());

    expect($content)->not->toContain('href=')
        ->and($content)->toContain('Linked task');
});

test('a hidden modal card action mounts nothing', function () {
    $html = Livewire::test(TestCardActionHiddenModalBoard::class)->html();

    expect($html)->not->toContain("mountAction('hiddenEdit'");
});

test('a modal card action marks its content as clickable', function () {
    $html = Livewire::test(TestCardActionModalBoard::class)->html();

    expect($html)->toMatch('/class="[^"]*\bff-clickable\b[^"]*"\s+wire:click="mountAction\(\'edit\'/');
});

test('card action inheriting a default action url renders as a link', function () {
    $title = modernCardTitleHtml(Livewire::test(TestCardActionDefaultUrlBoard::class)->html());

    expect($title)->toContain('href="https://example.test/default-action-url"')
        ->and($title)->not->toContain("mountAction('run'");
});

test('a url card action that also declares a confirmation renders as a link', function () {
    $title = modernCardTitleHtml(Livewire::test(TestCardActionConfirmBoard::class)->html());

    expect($title)->toContain('href="https://example.test/tasks/' . $this->task->getKey() . '"')
        ->and($title)->not->toContain("mountAction('urlWithConfirmation'");
});

test('a url card action that also declares a modal heading renders as a link', function () {
    $title = modernCardTitleHtml(Livewire::test(TestCardActionModalHeadingBoard::class)->html());

    expect($title)->toContain('href="https://example.test/tasks/' . $this->task->getKey() . '"')
        ->and($title)->not->toContain("mountAction('urlWithModalHeading'");
});

test('resolveCardAction finds the configured action among record actions', function () {
    $board = Livewire::test(TestCardActionBoard::class)->instance()->getBoard();

    $record = ['id' => $this->task->getKey(), 'model' => $this->task];

    expect($board->resolveCardAction($board->getBoardRecordActions($record))?->getName())->toBe('view');
});

test('resolveCardAction returns null when no card action is configured', function () {
    $board = Livewire::test(TestBoard::class)->instance()->getBoard();

    expect($board->resolveCardAction([]))->toBeNull();
});
