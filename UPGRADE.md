# Upgrading from v4.x to v5.x

v5 redesigns the board and adds column summaries, hard column limits, card components and swimlanes. It needs no migration, no config key and no env var.

### Requirements

- Filament 5 and PHP 8.3, as in v4.
- Groupings use window functions: MySQL 8, MariaDB 10.2, PostgreSQL, SQLite 3.25 or SQL Server. Boards without groupings have no new requirement.
- Hard limits take a cache lock, so use a store that every server shares, such as redis, memcached, database or dynamodb. The `array` store locks per process and the `null` store never blocks. Under either, two simultaneous drops can both pass a limit.

### Upgrade steps

1. `composer require relaticle/flowforge:^5.0`
2. `php artisan filament:assets`, which publishes the new stylesheet.
3. Standalone boards (outside a Filament panel) need `@filamentStyles` in their layout.
4. If you published Flowforge views or lang files, republish them and merge your changes.

### High impact changes

#### The board looks different

Columns are borderless trays with the header above them. The count is plain text instead of a badge, and `Column::color()` draws a dot before the label. An empty column shows no placeholder text, only its add row. Restyle through the `--ff-*` custom properties (see Design Tokens under Customization), not by copying views.

#### `moveCard()` signature and return type

```php
public function moveCard(
    string $cardId,
    string $targetColumnId,
    ?string $afterCardId = null,
    ?string $beforeCardId = null,
    ?string $fromLaneId = null,
    ?string $toLaneId = null,
): bool
```

v4 returned `void`. An override that keeps `: void` or the four-argument signature is now a fatal error.

`moveCard()` returns `false` when it refuses a move, and the browser puts the card back. Most overrides can go. Put checks in `authorizeCardMove(Model $card, string $targetColumnId, ?string $toLaneId = null): void` and custom writes in `persistCardMove(Model $card, mixed $columnValue, string $position): void`. Throw `CardMoveRejectedException::because('...')` from `authorizeCardMove()` to refuse with a message. An `abort(403)` there still reverts the card, but Livewire shows its error UI instead of a toast.

#### Moves to unknown columns and cards are refused

`moveCard()` refuses a target column the board does not show, and a card its query no longer returns. The user sees a notification and the card returns. v4 wrote any value the browser sent.

### Medium impact changes

#### The add row needs `createCardAction()`

```php
$board->createCardAction(
    CreateAction::make('createTask')->model(Task::class)->schema([...]),
);
```

Flowforge renders it as a `+` in each header and as an add row under the cards, and passes the column as `$arguments['column']`. It hides both when the action is not visible.

Flowforge owns the action's `disabled()` state. It disables the action for unknown and full columns, and, with `authorizationTooltip()`, when the action's authorization fails. Block creation for your own reasons with `authorize()` or `visible()`.

#### Dragging uses SortableJS fallback mode

The dragged card is a real element, so it can lift and tilt, and touch works. CSS that targeted native drag states, or code listening for `dragenter` on the board, stops working.

#### Published views and column paging state

- The `flowforge::column-count` component takes `column` and `columnId` instead of `color` and `total`. A published copy of a v4 view that renders it breaks.
- The `flowforge::empty-column` component is removed.
- `$columnCardLimits` is now `#[Locked]`, as are the new lane paging properties. JavaScript or a view that writes them from the browser gets a `CannotUpdateLockedPropertyException`. Call `loadMoreItems()` or `loadAllItems()` instead.
- `loadMoreItems()` clamps the count it loads into a lane cell to between 1 and `cardsPerColumn()`. The flat board still loads up to the count you pass, capped at the column total.

#### Position helpers gained a lane parameter

Moves across lanes pass a lane move through three protected methods. A subclass that overrides one with the v4 signature hits a PHP fatal error, because the override no longer accepts every parameter. These are the current signatures:

```php
protected function moveCardWithinLimit(
    Model $card,
    string $targetColumnId,
    ?string $afterCardId,
    ?string $beforeCardId,
    ?array $laneMove = null,
): string

protected function calculateAndUpdatePosition(
    Model $card,
    string $targetColumnId,
    ?string $afterCardId,
    ?string $beforeCardId,
    ?array $laneMove = null,
): string

protected function calculateAndUpdatePositionWithRetry(
    Model $card,
    string $targetColumnId,
    ?string $afterCardId,
    ?string $beforeCardId,
    int $maxAttempts = 3,
    ?array $laneMove = null,
): string
```

`moveCardWithinLimit()` is new in v5. The other two are v4 methods with `?array $laneMove = null` appended. Prefer `authorizeCardMove()` and `persistCardMove()` over overriding these.

### Low impact changes

- The `no_cards_in_column` lang key is removed, and the new keys exist in the en, de, es and nl files.
- Record actions moved to a menu in the card's top-right corner. It shows on hover and on keyboard focus where the device has hover.

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
