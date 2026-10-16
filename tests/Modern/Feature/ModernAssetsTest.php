<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard as ModernTestBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard as ClassicTestBoard;

test('a modern board loads its own script and stylesheet on request', function () {
    $html = Livewire::test(ModernTestBoard::class)->html();

    expect($html)->toContain('flowforge-modern.js')
        ->and($html)->toContain('flowforge-modern.css')
        ->and($html)->toContain('x-load-css')
        ->and($html)->toContain('flowforgeModern(');
});

test('a classic board references no modern asset', function () {
    $html = Livewire::test(ClassicTestBoard::class)->html();

    expect($html)->not->toContain('flowforge-modern')
        ->and($html)->not->toContain('flowforgeModern');
});
