<?php

declare(strict_types=1);

test('every bundled locale defines the same keys as English', function (string $locale): void {
    $english = array_keys(require __DIR__ . '/../../resources/lang/en/flowforge.php');
    $translated = array_keys(require __DIR__ . "/../../resources/lang/{$locale}/flowforge.php");

    expect($translated)->toEqualCanonicalizing($english);
})->with(['de', 'es', 'nl']);

test('the column label keeps the card label as given in each locale', function (string $locale): void {
    app()->setLocale($locale);

    expect(__('flowforge::flowforge.column_label', ['column' => 'Backlog', 'count' => 2, 'cards' => 'Datensätze']))
        ->toBe('Backlog, 2 Datensätze');
})->with(['en', 'de', 'es', 'nl']);
