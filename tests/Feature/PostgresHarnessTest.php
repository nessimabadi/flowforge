<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('the suite runs on the configured database', function () {
    expect(DB::connection()->getDriverName())->toBe(env('FLOWFORGE_TEST_DB', 'sqlite'));
})->group('pgsql');
