<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestCardComponentsBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\User;

function cardTask(array $attributes = []): Task
{
    return Task::create([
        'title' => 'Proration preview',
        'status' => 'todo',
        'priority' => 'high',
        'order_position' => '65535.0000000000',
        ...$attributes,
    ]);
}

function person(string $name): User
{
    return User::factory()->create(['name' => $name]);
}

function isHiddenCell(string $html, string $path): bool
{
    return preg_match('/schema-component::' . $path . '"[^>]*class="[^"]*\bfi-hidden\b/', $html) === 1;
}

function avatarCount(string $html): int
{
    return preg_match_all('/<(?:span|img)[^>]*class="[^"]*\bff-card-avatar\b[^"]*"/', $html);
}

test('a card title component replaces the default title', function () {
    cardTask();

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect(substr_count($html, '<h4'))->toBe(1)
        ->and($html)->toMatch('/<h4[^>]*>\s*(<!--.*?-->\s*)*Proration preview\s*(<!--.*?-->\s*)*<\/h4>/s');
});

test('a card tag renders a colored dot and its formatted label', function () {
    cardTask();

    Livewire::test(TestCardComponentsBoard::class)
        ->assertSeeHtml('--color-500:var(--danger-500)')
        ->assertSeeHtml('class="ff-card-tag-dot"')
        ->assertSeeHtml('class="ff-card-tag"');
});

test('a card tag formats its label with formatStateUsing', function () {
    cardTask();

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect($html)->toMatch('/<\/span>\s*High\s*<\/span>/');
});

test('a card tag with array state renders one tag per item', function () {
    cardTask(['labels' => ['bug', 'ui']]);

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect($html)
        ->toMatch('/<\/span>\s*bug\s*<\/span>/')
        ->toMatch('/<\/span>\s*ui\s*<\/span>/')
        ->not->toContain('["bug","ui"]')
        ->and(substr_count($html, '--color-500:var(--danger-500)'))->toBe(2);
});

test('a card tag with no value renders nothing', function () {
    cardTask(['priority' => '']);

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect(substr_count($html, 'ff-card-tags'))->toBe(1)
        ->and($html)->not->toContain('--color-500:var(--danger-500)')
        ->and(isHiddenCell($html, 'priority'))->toBeTrue();
});

test('card meta wraps its entries in the muted meta row', function () {
    cardTask(['description' => 'Needs a spec']);

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect(preg_match('/ff-card-meta[" ].*?fi-in-text-item.*?Needs a spec/s', $html))->toBe(1)
        ->and(preg_match('/ff-card-meta[" ].*?ff-card-tag.*?todo/s', $html))->toBe(1);
});

test('a card avatar falls back to initials and uses nameUsing for the names', function () {
    cardTask()->assignees()->attach(person('Emma Collins'));

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect($html)
        ->toContain('title="EMMA COLLINS"')
        ->toContain('aria-label="EMMA COLLINS"')
        ->toMatch('/>EC<\/span>/')
        ->and(avatarCount($html))->toBe(1)
        ->and(isHiddenCell($html, 'assignees'))->toBeFalse();
});

test('a card avatar stack caps at its limit and counts the rest', function () {
    $task = cardTask();
    $task->assignees()->attach([
        person('Emma Collins')->id,
        person('Marcus Johnson')->id,
        person('Chloe Nguyen')->id,
        person('Daniel Kim')->id,
    ]);

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect($html)
        ->toContain('title="EMMA COLLINS, MARCUS JOHNSON, CHLOE NGUYEN, DANIEL KIM"')
        ->toMatch('/>\+1<\/span>/')
        ->and(avatarCount($html))->toBe(3);
});

test('a card avatar renders an image when avatarUrlUsing returns a url', function () {
    $creator = person('Emma Collins');
    cardTask(['created_by' => $creator->id]);

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect($html)
        ->toContain('src="https://example.test/avatars/' . $creator->id . '.png"')
        ->toContain('title="Emma Collins"')
        ->and(avatarCount($html))->toBe(1);
});

test('a card without people renders no avatar stack', function () {
    cardTask();

    $html = Livewire::test(TestCardComponentsBoard::class)->html();

    expect($html)->not->toContain('ff-card-avatars')
        ->and(isHiddenCell($html, 'assignees'))->toBeTrue()
        ->and(isHiddenCell($html, 'creator'))->toBeTrue();
});
