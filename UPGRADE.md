# Upgrading from v4.1 to v4.2

4.2 adds the Modern board, an opt-in board with its own styling contract. Existing boards keep working unchanged. It needs no migration, no config key and no env var.

## Requirements

- Filament 5, PHP 8.3 and Laravel 12, as in 4.1.
- Groupings use window functions: MySQL 8, MariaDB 10.2, PostgreSQL, SQLite 3.25 or SQL Server. Boards without groupings have no new requirement.
- Hard column limits take a cache lock, so use a store that every server shares, such as redis, memcached, database or dynamodb. The `array` store locks per process and the `null` store never blocks. Under either, two simultaneous drops can both pass a limit.

## Upgrade steps

1. `composer update relaticle/flowforge`
2. `php artisan filament:assets`, which publishes the Modern script.

Nothing else changes for a board that keeps its current `BoardPage`, `BoardResourcePage` or `InteractsWithBoard` import.

## Opting in

Change one import on the page you want to move:

```php
use Relaticle\Flowforge\Modern\BoardResourcePage;
```

`Relaticle\Flowforge\Modern\BoardPage` replaces `BoardPage`, and `Relaticle\Flowforge\Modern\Concerns\InteractsWithBoard` replaces the standalone trait. Opt in per page. A classic and a Modern board can live in one app.

## What changes on a Modern board

- Overrides of `moveCard()` move to hooks. `moveCard()` returns `bool` and takes `?string $fromLaneId` and `?string $toLaneId`, so an override with the classic `void` signature is a fatal error. Put checks in `authorizeCardMove()` and custom writes in `persistCardMove()`. See [Custom Card Movement Logic](https://relaticle.github.io/flowforge/essentials/integration-patterns#custom-card-movement-logic).
- `calculateAndUpdatePosition()` and `calculateAndUpdatePositionWithRetry()` take a trailing `?array $laneMove = null`. An override with the classic signature is a fatal error. Prefer the two hooks.
- A move to a column the board does not show, or to a card its query no longer returns, is refused with a notification and the card returns.
- `createCardAction()`, column limits, column summaries, groupings and the card components (`CardTitle`, `CardTag`, `CardMeta`, `CardAvatar`) work on Modern boards only. A classic board ignores them.
- Modern markup carries `ff-*` classes. CSS written for `flowforge-*` classes does not reach a Modern board. Restyle through the [design system](https://relaticle.github.io/flowforge/essentials/design-system).
- Dragging uses SortableJS fallback mode, so the dragged card is a real element. CSS or code that targeted native drag states stops working.
- The paging properties (`$columnCardLimits`, `$visibleLaneCount`, `$cellCardLimits`) are `#[Locked]`. Call `loadMoreItems()` or `loadAllItems()` instead of writing them from the browser.
- Modern views live under `flowforge::modern.*`. A classic view you published to `resources/views/vendor/flowforge/livewire` keeps serving classic boards and never reaches a Modern board.

## Other changes

- New lang keys exist in the en, de, es and nl files. The classic `no_cards_in_column` key stays.
- A board query that joins another table must select the base table's columns explicitly. See [Joined board queries](https://relaticle.github.io/flowforge/essentials/integration-patterns#joined-board-queries).
- A classic move no longer fails on PostgreSQL when the board query outer-joins its column value.

## Known limitation

The first `wire:navigate` visit from a classic page to a Modern board can show the board unstyled for a moment, because Livewire does not wait for new head stylesheets. Full page loads and repeat visits are styled from the first frame.

---

# Upgrading from v2.x to v3.x

This guide covers migrating from Flowforge v2.x (Lexorank/string-based positions) to v3.x (DecimalPosition/DECIMAL-based positions).

## Breaking Changes

1. **New Dependency**: `ext-bcmath` PHP extension required
2. **Column Type**: Position column changed from `VARCHAR` to `DECIMAL(20,10)`
3. **Service Removed**: `Rank.php` replaced by `DecimalPosition.php`
4. **Laravel Version**: Now requires Laravel 12+

## Migration Steps

### Step 1: Check PHP Extensions

Ensure the BCMath extension is installed:

```bash
php -m | grep bcmath
```

If not installed, add it to your PHP configuration.

### Step 2: Update Dependencies

```bash
composer require relaticle/flowforge:^3.0
```

### Step 3: Update Position Column

Create a migration to change the position column type:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Option A: Drop and recreate (loses existing positions)
        Schema::table('your_table', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('your_table', function (Blueprint $table) {
            $table->flowforgePositionColumn();
            $table->unique(['status', 'position'], 'unique_position_per_column');
        });

        // Option B: Keep existing order (recommended)
        // See "Preserving Existing Order" section below
    }

    public function down(): void
    {
        Schema::table('your_table', function (Blueprint $table) {
            $table->dropUnique('unique_position_per_column');
            $table->dropColumn('position');
        });

        Schema::table('your_table', function (Blueprint $table) {
            $table->string('position')->nullable();
        });
    }
};
```

### Step 4: Regenerate Positions

After migration, regenerate all positions:

```bash
php artisan flowforge:repair-positions
```

Select the **"regenerate"** strategy when prompted.

### Step 5: Update Code References

Replace all references to the old `Rank` service:

```diff
- use Relaticle\Flowforge\Services\Rank;
+ use Relaticle\Flowforge\Services\DecimalPosition;

// Empty column position
- $position = Rank::forEmptySequence()->get();
+ $position = DecimalPosition::forEmptyColumn();

// Position after another
- $position = Rank::after($lastRank)->get();
+ $position = DecimalPosition::after($lastPosition);

// Position before another
- $position = Rank::before($nextRank)->get();
+ $position = DecimalPosition::before($nextPosition);

// Position between two
- $position = Rank::betweenRanks($prevRank, $nextRank)->get();
+ $position = DecimalPosition::between($afterPos, $beforePos);
```

### Step 6: Verify Data Integrity

Run the diagnose command to check for issues:

```bash
php artisan flowforge:diagnose-positions \
    --model=App\\Models\\YourModel \
    --column=status \
    --position=position
```

## Preserving Existing Order

If you need to preserve existing card order during migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Relaticle\Flowforge\Services\DecimalPosition;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new decimal column
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('position_new', 20, 10)->nullable();
        });

        // 2. Convert existing positions maintaining order
        $statuses = DB::table('tasks')->distinct()->pluck('status');

        foreach ($statuses as $status) {
            $tasks = DB::table('tasks')
                ->where('status', $status)
                ->orderBy('position')  // Original string order
                ->get();

            $lastPosition = null;
            foreach ($tasks as $task) {
                $newPosition = $lastPosition === null
                    ? DecimalPosition::forEmptyColumn()
                    : DecimalPosition::after($lastPosition);

                DB::table('tasks')
                    ->where('id', $task->id)
                    ->update(['position_new' => $newPosition]);

                $lastPosition = $newPosition;
            }
        }

        // 3. Swap columns
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->renameColumn('position_new', 'position');
            $table->unique(['status', 'position'], 'unique_position_per_column');
        });
    }
};
```

## New Features in v3.x

### Jitter for Concurrent Safety

Each position calculation now includes ±5% random jitter, preventing collisions when multiple users move cards simultaneously.

### Auto-Rebalancing

When gaps between positions fall below `0.0001`, positions are automatically redistributed with `65535` spacing.

### Retry Mechanism

Unique constraint violations trigger automatic retry with exponential backoff (50ms, 100ms, 200ms).

### New Artisan Commands

- `flowforge:diagnose-positions` - Check for position issues
- `flowforge:rebalance-positions` - Redistribute positions evenly
- `flowforge:repair-positions` - Interactive repair wizard

## API Changes

### DecimalPosition Methods

| v2.x (Rank) | v3.x (DecimalPosition) | Notes |
|-------------|------------------------|-------|
| `Rank::forEmptySequence()` | `DecimalPosition::forEmptyColumn()` | Returns string directly |
| `Rank::after($rank)` | `DecimalPosition::after($position)` | Returns string directly |
| `Rank::before($rank)` | `DecimalPosition::before($position)` | Returns string directly |
| `Rank::betweenRanks($a, $b)` | `DecimalPosition::between($a, $b)` | Includes jitter |
| `$rank->get()` | N/A | No longer needed, returns string |

### Blueprint Macro

The `flowforgePositionColumn()` macro now creates `DECIMAL(20,10)` instead of `VARCHAR`:

```php
// v2.x created:
$table->string('position')->nullable()->collation('utf8mb4_bin');

// v3.x creates:
$table->decimal('position', 20, 10)->nullable();
```

## Troubleshooting

### BCMath Not Found

```
Error: Call to undefined function bcadd()
```

Install the BCMath extension:
- **Ubuntu/Debian**: `sudo apt-get install php-bcmath`
- **macOS (Homebrew)**: Usually included, check `php -m | grep bcmath`
- **Windows**: Enable in php.ini: `extension=bcmath`

### Position Collisions

If you see unique constraint violations after migration:

```bash
php artisan flowforge:repair-positions
# Select "regenerate" strategy
```

### Order Not Preserved

If card order changed after migration, the original string positions may not have been sorted correctly. Run:

```bash
php artisan flowforge:repair-positions
# Select "regenerate" strategy
```

This will reassign positions based on current database order.
