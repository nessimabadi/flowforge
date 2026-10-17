<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Tests\Fixtures\Modern\Task;

beforeEach(function () {
    Task::create([
        'title' => 'Reference Card A',
        'status' => 'todo',
        'order_position' => '1000.0000000000',
    ]);

    Task::create([
        'title' => 'Reference Card B',
        'status' => 'todo',
        'order_position' => '2000.0000000000',
    ]);
});

describe('concurrent position insertions', function () {
    test('50 insertions at same position produce unique positions', function () {
        $afterPos = '1000.0000000000';
        $beforePos = '2000.0000000000';
        $insertedPositions = [];
        $failedInserts = 0;

        for ($i = 0; $i < 50; $i++) {
            $position = DecimalPosition::between($afterPos, $beforePos);

            try {
                $task = Task::create([
                    'title' => "Concurrent Card {$i}",
                    'status' => 'todo',
                    'order_position' => $position,
                ]);
                $insertedPositions[] = $task->order_position;
            } catch (QueryException $e) {
                $failedInserts++;
            }
        }

        expect(array_unique($insertedPositions))->toHaveCount(count($insertedPositions))
            ->and($failedInserts)->toBe(0);

        foreach ($insertedPositions as $position) {
            $posStr = DecimalPosition::normalize($position);
            expect(bccomp($posStr, $afterPos, 10))->toBeGreaterThan(0)
                ->and(bccomp($posStr, $beforePos, 10))->toBeLessThan(0);
        }

        $orderedTasks = Task::where('status', 'todo')
            ->whereNotIn('id', [1, 2])
            ->orderBy('order_position')
            ->get();

        expect($orderedTasks)->toHaveCount(50);

        $previousPosition = '0';
        foreach ($orderedTasks as $task) {
            $posStr = DecimalPosition::normalize($task->order_position);
            expect(bccomp($posStr, $previousPosition, 10))
                ->toBeGreaterThan(0);
            $previousPosition = $posStr;
        }
    });

    test('rapid successive insertions by same user dont collide', function () {
        $afterPos = '1000.0000000000';
        $beforePos = '2000.0000000000';
        $insertedCount = 0;

        for ($i = 0; $i < 50; $i++) {
            $position = DecimalPosition::between($afterPos, $beforePos);

            try {
                Task::create([
                    'title' => "Rapid Card {$i}",
                    'status' => 'todo',
                    'order_position' => $position,
                ]);
                $insertedCount++;
            } catch (QueryException) {
            }
        }

        expect($insertedCount)->toBe(50);

        $positions = Task::where('status', 'todo')
            ->whereNotIn('id', [1, 2])
            ->pluck('order_position')
            ->toArray();

        expect(array_unique($positions))->toHaveCount(50);
    });

    test('unique constraint actually prevents duplicate positions', function () {
        Task::create([
            'title' => 'First Card',
            'status' => 'todo',
            'order_position' => '1500.0000000000',
        ]);

        expect(fn () => Task::create([
            'title' => 'Duplicate Card',
            'status' => 'todo',
            'order_position' => '1500.0000000000',
        ]))->toThrow(QueryException::class);
    });

    test('positions remain sortable after many insertions', function () {
        $afterPos = '1000.0000000000';
        $beforePos = '2000.0000000000';

        for ($i = 0; $i < 100; $i++) {
            $position = DecimalPosition::between($afterPos, $beforePos);
            Task::create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $position,
            ]);
        }

        $tasks = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->get();

        expect($tasks)->toHaveCount(102);

        $previousPosition = '-999999';
        foreach ($tasks as $task) {
            $posStr = DecimalPosition::normalize($task->order_position);
            expect(bccomp($posStr, $previousPosition, 10))
                ->toBeGreaterThan(0);
            $previousPosition = $posStr;
        }
    });
});

describe('position collision statistics', function () {
    test('jitter produces statistically unique positions', function () {
        $afterPos = '1000.0000000000';
        $beforePos = '2000.0000000000';
        $positions = [];

        for ($i = 0; $i < 1000; $i++) {
            $positions[] = DecimalPosition::between($afterPos, $beforePos);
        }

        $uniquePositions = array_unique($positions);

        expect(count($uniquePositions))->toBe(1000);

        $midpoint = DecimalPosition::betweenExact($afterPos, $beforePos);
        $belowMidpoint = 0;
        $aboveMidpoint = 0;

        foreach ($positions as $position) {
            if (bccomp($position, $midpoint, 10) < 0) {
                $belowMidpoint++;
            } else {
                $aboveMidpoint++;
            }
        }

        expect($belowMidpoint)->toBeGreaterThan(400)
            ->and($belowMidpoint)->toBeLessThan(600)
            ->and($aboveMidpoint)->toBeGreaterThan(400)
            ->and($aboveMidpoint)->toBeLessThan(600);
    });
});
