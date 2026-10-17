<?php

declare(strict_types=1);

function designDocsSection(string $heading): string
{
    $page = file_get_contents(__DIR__ . '/../../../docs/content/2.essentials/7.design-system.md');

    preg_match('/^## ' . preg_quote($heading, '/') . '\R(.*?)(?=^## |\z)/ms', $page, $matches);

    return $matches[1] ?? '';
}

/**
 * @return array<int, array<int, string>>
 */
function designDocsRows(string $heading): array
{
    $rows = [];

    foreach (preg_split('/\R/', designDocsSection($heading)) as $line) {
        if (! str_starts_with($line, '| `')) {
            continue;
        }

        $rows[] = array_map(trim(...), explode(' | ', trim($line, '| ')));
    }

    return $rows;
}

test('the variables table matches the token source of truth', function () {
    $documented = [];

    foreach (designDocsRows('Variables') as $cells) {
        $documented[trim($cells[0], '`')] = ['tier' => $cells[1], 'since' => $cells[3]];
    }

    expect($documented)->toBe(require __DIR__ . '/../../../resources/design-tokens.php');
});

test('the hooks table lists exactly the seventeen hooks', function () {
    $documented = array_map(fn (array $cells): string => trim($cells[0], '`'), designDocsRows('Hooks'));

    expect($documented)->toEqualCanonicalizing([
        'ff-board', 'ff-columns', 'ff-column', 'ff-column-header', 'ff-count', 'ff-summary', 'ff-tray', 'ff-card', 'ff-card-actions',
        'ff-add-row', 'ff-lanes', 'ff-lane-header', 'ff-cell', 'ff-limit-hint', 'ff-card-tag', 'ff-card-meta', 'ff-card-avatar',
    ]);
});

test('the states table lists exactly the six states', function () {
    $documented = array_map(
        fn (array $cells): string => preg_match('/data-[a-z]+/', $cells[0], $match) === 1 ? $match[0] : $cells[0],
        designDocsRows('States'),
    );

    expect($documented)->toEqualCanonicalizing([
        'data-dragging', 'data-placeholder', 'data-drop', 'data-full', 'data-collapsed', 'data-pending',
    ]);
});
