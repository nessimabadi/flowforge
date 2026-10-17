<?php

declare(strict_types=1);

use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\BrowserUser;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard as ModernTestBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestStandaloneBoard as ModernStandaloneBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard as ClassicTestBoard;

test('a modern board page loads its own script', function () {
    $html = Livewire::test(ModernTestBoard::class)->html();

    expect($html)->toContain('flowforge-modern.js')
        ->and($html)->toContain('flowforgeModern(');
});

test('a modern panel page links the stylesheet once, in the head', function () {
    $this->actingAs(BrowserUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']));

    $html = $this->get('/modern/test-board')->assertOk()->getContent();
    [$head, $body] = explode('</head>', $html, 2);

    expect(preg_match_all('/<link\b[^>]*rel="stylesheet" data-navigate-track[^>]*flowforge-modern\.css[^>]*>/i', $head))->toBe(1)
        ->and($body)->not->toContain('flowforge-modern.css')
        ->and($html)->not->toContain('x-load-css');
});

test('a classic panel page links no modern stylesheet', function () {
    $this->actingAs(BrowserUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']));

    expect($this->get('/modern/test-classic-board')->assertOk()->getContent())->not->toContain('flowforge-modern')
        ->and($this->get('/admin/test-board')->assertOk()->getContent())->not->toContain('flowforge-modern');
});

test('a standalone modern board links the stylesheet ahead of the board inside its single root', function () {
    $html = Livewire::test(ModernStandaloneBoard::class)->html();

    preg_match_all('/<link\b[^>]*flowforge-modern\.css[^>]*>/i', $html, $stylesheetTags);

    expect($stylesheetTags[0])->toHaveCount(1)
        ->and($stylesheetTags[0][0])->toContain('rel="stylesheet"')
        ->and(strpos($html, '<div style="display: contents">'))->toBeLessThan(strpos($html, 'flowforge-modern.css'))
        ->and(strpos($html, 'flowforge-modern.css'))->toBeLessThan(strpos($html, 'class="ff-board"'));
});

test('a classic board references no modern asset', function () {
    $html = Livewire::test(ClassicTestBoard::class)->html();

    expect($html)->not->toContain('flowforge-modern')
        ->and($html)->not->toContain('flowforgeModern');
});

test('the panel assets include no modern asset', function () {
    expect(FilamentAsset::renderStyles(['relaticle/flowforge']))->not->toContain('flowforge-modern')
        ->and(FilamentAsset::renderScripts(['relaticle/flowforge']))->not->toContain('flowforge-modern');
});
