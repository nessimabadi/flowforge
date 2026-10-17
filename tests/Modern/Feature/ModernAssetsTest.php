<?php

declare(strict_types=1);

use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard as ModernTestBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard as ClassicTestBoard;

test('a modern board loads its own script and stylesheet on request', function () {
    $html = Livewire::test(ModernTestBoard::class)->html();

    expect($html)->toContain('flowforge-modern.js')
        ->and($html)->toContain('flowforge-modern.css')
        ->and($html)->toContain('flowforgeModern(');
});

test('the stylesheet is a plain link ahead of the board inside the single root', function () {
    $html = Livewire::test(ModernTestBoard::class)->html();

    preg_match_all('/<link\b[^>]*flowforge-modern\.css[^>]*>/i', $html, $stylesheetTags);

    expect($stylesheetTags[0])->toHaveCount(1)
        ->and($stylesheetTags[0][0])->toContain('rel="stylesheet"')
        ->and($html)->not->toContain('x-load-css')
        ->and(strpos($html, 'flowforge-modern.css'))->toBeLessThan(strpos($html, 'class="ff-board"'))
        ->and(strpos($html, '<div style="display: contents">'))->toBeLessThan(strpos($html, 'flowforge-modern.css'));
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
