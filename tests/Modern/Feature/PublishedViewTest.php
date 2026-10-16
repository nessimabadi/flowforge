<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task as ModernTask;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard as ModernTestBoard;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard as ClassicTestBoard;

beforeEach(function () {
    $this->publishedPath = sys_get_temp_dir() . '/flowforge-published-' . uniqid();

    File::ensureDirectoryExists("{$this->publishedPath}/livewire");
    File::put("{$this->publishedPath}/livewire/column.blade.php", 'CLASSIC-OVERRIDE');

    View::getFinder()->prependNamespace('flowforge', $this->publishedPath);
});

afterEach(function () {
    File::deleteDirectory($this->publishedPath);
});

test('a published classic view does not reach a modern board', function () {
    ModernTask::factory()->todo()->create();
    Task::factory()->todo()->create();

    Livewire::test(ModernTestBoard::class)->assertDontSee('CLASSIC-OVERRIDE');
    Livewire::test(ClassicTestBoard::class)->assertSee('CLASSIC-OVERRIDE');
});
