<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Components\CardAvatar;
use Relaticle\Flowforge\Components\CardMeta;
use Relaticle\Flowforge\Components\CardTag;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestContractBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\User;

function contractBoardHtml(): string
{
    $assignee = User::factory()->create(['name' => 'Emma Collins']);

    foreach (['Alpha' => '65535.0000000000', 'Beta' => '131070.0000000000'] as $title => $position) {
        Task::create([
            'title' => $title,
            'status' => 'todo',
            'priority' => 'high',
            'description' => 'Needs a spec',
            'order_position' => $position,
        ])->assignees()->attach($assignee);
    }

    return implode("\n", [
        Livewire::test(TestContractBoard::class)->html(),
        Livewire::test(TestContractBoard::class, ['withHeaderToolbar' => true])->html(),
        Livewire::test(TestContractBoard::class)->set('groupBy', 'priority')->html(),
    ]);
}

function contractCss(): string
{
    return file_get_contents(__DIR__ . '/../../../resources/css/flowforge-modern.css');
}

function contractCssWithoutComments(): string
{
    return preg_replace('#/\*.*?\*/#s', '', contractCss());
}

function contractTokens(): array
{
    return require __DIR__ . '/../../../resources/design-tokens.php';
}

function contractClassTokens(string $html): array
{
    preg_match_all('/(?<![\w:-])class="([^"]*)"/', $html, $matches);

    return array_values(array_unique(array_merge(...array_map(
        fn (string $classes): array => preg_split('/\s+/', trim($classes), -1, PREG_SPLIT_NO_EMPTY),
        $matches[1],
    ))));
}

test('a modern board renders every documented hook', function (string $hook) {
    expect(contractBoardHtml())->toMatch('/(?<![\w:-])class="[^"]*(?<![\w-])' . $hook . '(?![\w-])/');
})->with([
    'ff-board', 'ff-column', 'ff-column-header', 'ff-count', 'ff-summary', 'ff-tray', 'ff-card',
    'ff-card-actions', 'ff-add-row', 'ff-lanes', 'ff-lane-header', 'ff-cell', 'ff-limit-hint',
    'ff-card-tag', 'ff-card-meta', 'ff-card-avatar',
]);

test('a modern board renders the server-side states', function () {
    $html = contractBoardHtml();

    expect($html)
        ->toMatch('/<div\s[^>]*class="ff-column"[^>]*\sdata-full[\s>]/')
        ->toMatch('/<div\s[^>]*class="ff-column"[^>]*\sdata-collapsed[\s>]/');
});

test('a modern board binds the drop state on columns and lane cells', function () {
    $html = contractBoardHtml();

    expect($html)
        ->toContain('x-bind:data-drop="columnDrop(')
        ->toContain('x-bind:data-drop="cellDrop(');
});

test('modern markup carries only ff and filament classes', function () {
    $foreign = array_values(array_filter(
        contractClassTokens(contractBoardHtml()),
        fn (string $token): bool => preg_match('/^(?:[a-z-]+:)*(?:ff|fi)-/', $token) !== 1,
    ));

    expect($foreign)->toBe([]);
});

test('modern markup carries no classic classes', function () {
    expect(contractBoardHtml())->not->toMatch('/(?<![\w:-])class="[^"]*\bflowforge-[a-z]/');
});

test('modern view files carry no utility or classic classes', function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../../resources/views/modern'));
    $offenders = [];

    foreach ($files as $file) {
        if (! $file->isFile()) {
            continue;
        }

        preg_match_all('/(?<![\w:-])class="([^"]*)"/', file_get_contents($file->getPathname()), $matches);

        foreach ($matches[1] as $classes) {
            if (preg_match('/(?<![\w-])(?:flowforge-|(?:p|px|py|m|mx|my|gap|text|bg|rounded|flex|grid|w|h|size)-|flex\b|grid\b|hidden\b|block\b)/', $classes) === 1) {
                $offenders[] = basename($file->getPathname()) . ': ' . $classes;
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('the stylesheet reads exactly the documented variables', function () {
    preg_match_all('/var\((--ff-[a-z-]+)/', contractCss(), $matches);

    expect(array_values(array_unique($matches[1])))->toEqualCanonicalizing(array_keys(contractTokens()));
});

test('every documented variable names a tier and a version', function () {
    foreach (contractTokens() as $name => $token) {
        expect($name)->toStartWith('--ff-')
            ->and($token['tier'])->toBeIn(['semantic', 'component'])
            ->and($token['since'])->toBe('4.2.0');
    }
});

test('the stylesheet never sets a public variable', function () {
    expect(contractCssWithoutComments())->not->toMatch('/--ff-[a-z-]+\s*:/');
});

test('the stylesheet avoids apply and light-dark', function () {
    expect(contractCssWithoutComments())->not->toContain('@apply')->not->toContain('light-dark(');
});

test('every rule sits in the flowforge layer', function () {
    $css = contractCssWithoutComments();
    $statement = '@layer properties, theme, base, components, utilities;';

    expect(trim($css))->toStartWith($statement);

    $rest = trim(substr(trim($css), strlen($statement)));

    expect($rest)->toStartWith('@layer components.flowforge {')->and($rest)->toEndWith('}');

    $depth = 0;
    $closedEarly = false;

    foreach (str_split($rest) as $index => $character) {
        $depth += $character === '{' ? 1 : ($character === '}' ? -1 : 0);
        $closedEarly = $closedEarly || ($depth === 0 && $index > 0 && $index < strlen($rest) - 1 && $character === '}');
    }

    expect($closedEarly)->toBeFalse();
});

test('the ghost mirrors exactly the documented variables', function () {
    preg_match_all("/'(--ff-[a-z-]+)'/", file_get_contents(__DIR__ . '/../../../resources/js/flowforge-modern.js'), $matches);

    expect($matches[1])->toEqualCanonicalizing(array_keys(contractTokens()));
});

test('card tags and avatars do not grow inside a card flex', function () {
    Task::create(['title' => 'Alpha', 'status' => 'todo', 'priority' => 'high', 'description' => 'Needs a spec', 'order_position' => '65535.0000000000']);

    $html = Livewire::test(TestContractBoard::class)->html();

    expect(CardTag::make('priority')->canGrow())->toBeFalse()
        ->and(CardAvatar::make('assignees')->canGrow())->toBeFalse()
        ->and(CardMeta::make([])->canGrow())->toBeFalse()
        ->and(substr_count($html, 'ff-card-flex-item--grow'))->toBe(1)
        ->and(substr_count($html, 'ff-card-flex-item--fixed'))->toBeGreaterThanOrEqual(2);
});

test('card meta hides its entries labels', function () {
    Task::create(['title' => 'Alpha', 'status' => 'todo', 'description' => 'Needs a spec', 'order_position' => '65535.0000000000']);

    $html = Livewire::test(TestContractBoard::class)->html();

    expect($html)->toContain('Needs a spec')
        ->toMatch('/class="fi-in-entry-label fi-sr-only"[^>]*>\s*Description/')
        ->not->toMatch('/class="fi-in-entry-label"/');
});
