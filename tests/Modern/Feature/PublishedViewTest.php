<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task as ModernTask;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard as ModernTestBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardComponentsBoard;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard as ClassicTestBoard;

const PUBLISHABLE_CLASSIC_VIEWS = [
    'components/card-flex',
    'components/filters',
    'filament/pages/board-header',
    'filament/pages/board-page',
    'index',
    'livewire/card',
    'livewire/column-count',
    'livewire/column',
    'livewire/empty-column',
];

beforeEach(function () {
    $this->publishedPath = sys_get_temp_dir() . '/flowforge-published-' . uniqid();

    foreach (PUBLISHABLE_CLASSIC_VIEWS as $view) {
        File::ensureDirectoryExists(dirname("{$this->publishedPath}/{$view}"));
        File::put("{$this->publishedPath}/{$view}.blade.php", '<div>CLASSIC-OVERRIDE:' . $view . '</div>');
    }

    View::getFinder()->prependNamespace('flowforge', $this->publishedPath);

    ModernTask::factory()->todo()->create(['title' => 'Known card']);
});

afterEach(function () {
    File::deleteDirectory($this->publishedPath);
});

test('every classic view path can be published', function () {
    $released = collect(explode("\n", trim((string) shell_exec('git ls-tree -r --name-only v4.1.4 resources/views'))))
        ->map(fn (string $path): string => str($path)->after('resources/views/')->before('.blade.php')->toString())
        ->all();

    expect(PUBLISHABLE_CLASSIC_VIEWS)->toEqualCanonicalizing($released);
});

test('a published classic view does not reach a modern board', function (string $board, array $properties) {
    $html = Livewire::test($board, $properties)->assertSee('Known card')->html();

    expect($html)->not->toContain('CLASSIC-OVERRIDE');
})->with([
    'plain board' => [ModernTestBoard::class, []],
    'header toolbar' => [ModernTestBoard::class, ['withHeaderToolbar' => true]],
    'card components' => [TestCardComponentsBoard::class, []],
]);

test('a published classic view reaches a classic board', function () {
    Task::factory()->todo()->create(['title' => 'Known card']);

    Livewire::test(ClassicTestBoard::class)->assertSee('CLASSIC-OVERRIDE:filament/pages/board-page');
});
