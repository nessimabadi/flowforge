<?php

declare(strict_types=1);

use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard;

describe('snowflake ID precision', function () {
    test('formatBoardRecord casts record ID to string to prevent JS precision loss', function () {
        $task = Task::factory()->todo()->withPosition('65535.0000000000')->create();

        $task->id = 420533451316027392;

        $board = app(TestBoard::class)->getBoard();
        $formatted = $board->formatBoardRecord($task);

        expect($formatted['id'])->toBeString();
        expect($formatted['id'])->toBe('420533451316027392');
    });

    test('json_encode emits quoted recordKey for large string IDs', function () {
        $snowflakeId = 420533451316027392;

        $jsonSnowflakeStr = json_encode(['recordKey' => (string) $snowflakeId]);
        $jsonSnowflakeInt = json_encode(['recordKey' => $snowflakeId]);

        expect($jsonSnowflakeStr)->toContain('"420533451316027392"');

        expect($jsonSnowflakeInt)->not->toContain('"420533451316027392"');
    });
});
