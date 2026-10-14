---
name: flowforge-development
description: Builds Kanban board interfaces for Eloquent models with drag-and-drop functionality. Use when creating board pages, configuring columns and cards, implementing drag-and-drop positioning, working with Filament board pages or standalone Livewire boards, or troubleshooting position-related issues.
---

# Flowforge Development

## When to Use This Skill

Use when:
- Creating Kanban board interfaces for Eloquent models
- Configuring board columns, cards, and actions
- Implementing drag-and-drop with position management
- Building Filament board pages or standalone Livewire boards
- Troubleshooting position column issues

## Quick Start

### 1. Add Position Column to Model

```php
use Illuminate\Database\Schema\Blueprint;

Schema::table('tasks', function (Blueprint $table) {
    $table->flowforgePositionColumn(); // DECIMAL(20,10) nullable
    $table->unique(['status', 'position']);
});
```

### 2. Create Board Page

```bash
php artisan flowforge:make-board TaskBoard
```

### 3. Configure the Board

```php
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;

class TaskBoard extends BoardPage
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    public function board(Board $board): Board
    {
        return $board
            ->query(Task::query())
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->recordTitleAttribute('title')
            ->columns([
                Column::make('todo', 'To Do')
                    ->icon('heroicon-o-clipboard'),
                Column::make('in_progress', 'In Progress')
                    ->icon('heroicon-o-play'),
                Column::make('done', 'Done')
                    ->icon('heroicon-o-check'),
            ]);
    }
}
```

## Integration Patterns

### Filament Standard Page

```php
use Relaticle\Flowforge\BoardPage;

class TaskBoard extends BoardPage
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationGroup = 'Tasks';

    public function board(Board $board): Board
    {
        return $board
            ->query(Task::query()->where('team_id', auth()->user()->team_id))
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->columns([...]);
    }
}
```

### Filament Resource Page

```php
use Relaticle\Flowforge\BoardResourcePage;

class TaskBoardPage extends BoardResourcePage
{
    protected static string $resource = TaskResource::class;

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getResource()::getEloquentQuery())
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->columns([...]);
    }
}
```

Register in resource:

```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListTasks::route('/'),
        'board' => Pages\TaskBoardPage::route('/board'),
    ];
}
```

### Standalone Livewire Component

```php
use Livewire\Component;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Contracts\HasBoard;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;

class TaskBoard extends Component implements HasBoard
{
    use InteractsWithBoard;

    public function board(Board $board): Board
    {
        return $board
            ->query(Task::query())
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->columns([...]);
    }

    public function render()
    {
        return view('livewire.task-board');
    }
}
```

Blade view:

```blade
<div>
    {{ $this->board }}
</div>
```

## Board Configuration

### Columns

```php
use Relaticle\Flowforge\Column;

->columns([
    Column::make('backlog', 'Backlog')
        ->icon('heroicon-o-inbox')
        ->color('gray'),

    Column::make('todo', 'To Do')
        ->icon('heroicon-o-clipboard')
        ->color('info'),

    Column::make('in_progress', 'In Progress')
        ->icon('heroicon-o-play')
        ->color('warning'),

    Column::make('review', 'Review')
        ->icon('heroicon-o-eye')
        ->color('primary'),

    Column::make('done', 'Done')
        ->icon('heroicon-o-check-circle')
        ->color('success'),
])
```

### Card Schema

Use Filament's Schema builder for rich card layouts:

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Grid;

->cardSchema([
    Grid::make(2)
        ->schema([
            TextEntry::make('title')
                ->weight('bold'),
            TextEntry::make('priority')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'high' => 'danger',
                    'medium' => 'warning',
                    default => 'gray',
                }),
        ]),
    TextEntry::make('assignee.name')
        ->icon('heroicon-o-user'),
    TextEntry::make('due_date')
        ->date()
        ->icon('heroicon-o-calendar'),
])
```

### Pagination

```php
->cardsPerColumn(20)           // Cards loaded initially
->cardsIncrement(10)           // Cards loaded on "Load More"
```

### Search

```php
->searchable(['title', 'description'])
```

### Filters

```php
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

->filters([
    SelectFilter::make('priority')
        ->options([
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ]),
    SelectFilter::make('assignee_id')
        ->relationship('assignee', 'name')
        ->searchable()
        ->preload(),
    TernaryFilter::make('is_overdue')
        ->label('Overdue'),
])
```

### Actions

**Record Actions** (per card):

```php
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

->recordActions([
    EditAction::make()
        ->url(fn ($record) => route('tasks.edit', $record)),
    Action::make('archive')
        ->icon('heroicon-o-archive-box')
        ->action(fn ($record) => $record->archive()),
    DeleteAction::make(),
])
```

**Column Actions** (per column header):

```php
->columnActions([
    Action::make('add')
        ->icon('heroicon-o-plus')
        ->action(function (array $arguments) {
            // $arguments['column'] contains column identifier
            Task::create([
                'status' => $arguments['column'],
                'position' => DecimalPosition::forEmptyColumn(),
            ]);
        }),
])
```

### Add Card Action

```php
->createCardAction(
    CreateAction::make('createTask')
        ->model(Task::class)
        ->schema([TextInput::make('title')->required()])
        ->mutateDataUsing(fn (array $data, array $arguments): array => [
            ...$data,
            'status' => $arguments['column'],
            'position' => $this->getBoardPositionInColumn($arguments['column'], 'bottom'),
        ]),
)
```

Renders a `+` in each column header and an add row under the cards. Flowforge owns the action's `disabled()` state and disables it for full and unknown columns. Use `visible()` or `authorize()` to block creation.

### Column Limits

```php
Column::make('in_progress')->limit(3)
```

The header reads `3/3`, a full column refuses drops while the card hovers, and the add row disables. The limit counts every card in the column, including cards a filter hides. The server re-checks under a cache lock, so use a shared cache store with locks (redis, memcached, database, dynamodb): the `array` store locks per process and `null` never blocks.

### Column Summaries

```php
->columnSummary(fn (Builder $columnQuery): ?string => Number::currency($columnQuery->sum('amount')))
```

The closure gets the column's query with the active filters and search applied. Return `null` to hide the summary.

### Swimlanes

```php
->groupings([
    Grouping::attribute('priority'),

    Grouping::make('assignee')
        ->lanes(fn (Builder $boardQuery): array => $this->assigneeLanes($boardQuery))
        ->emptyLane('Unassigned', fn (Builder $query): Builder => $query->whereDoesntHave('assignees'))
        ->moveUsing(function (Task $card, Lane $from, Lane $to): void {
            // sync the relation: $from is the lane the card left, $to the lane it entered
        }),
])
```

A "Group by" select appears and the choice lives in `?groupBy=`. `lanes()` returns `Lane::make($name)->label()->query(fn (Builder $query) => ...)` objects. Give every custom grouping an `emptyLane()`, because the header `+` creates a card without knowing the lane. Without `moveUsing()` the lanes are read-only. Throw `CardMoveRejectedException::because('...')` in `moveUsing()` to refuse a move and roll back both writes. `collapseEmptyColumns()` applies to the plain board only.

## Position Management

Flowforge uses `DECIMAL(20,10)` positions with BCMath precision for reliable ordering.

### DecimalPosition Service

```php
use Relaticle\Flowforge\Services\DecimalPosition;

// Position between two cards (includes cryptographic jitter)
$position = DecimalPosition::between($afterPosition, $beforePosition);

// Exact midpoint (deterministic, for testing)
$position = DecimalPosition::betweenExact($afterPosition, $beforePosition);

// Position before first card
$position = DecimalPosition::before($firstPosition);

// Position after last card
$position = DecimalPosition::after($lastPosition);

// Initial position for empty column
$position = DecimalPosition::forEmptyColumn();

// Smart positioning (handles nulls)
$position = DecimalPosition::calculate($afterPos, $beforePos);

// Check if rebalancing needed
if (DecimalPosition::needsRebalancing($posA, $posB)) {
    // Gap is < 0.0001
}

// Generate evenly-spaced sequence
$positions = DecimalPosition::generateSequence(count: 100);
```

### Manual Card Movement

Override the hooks, not `moveCard()`. Flowforge keeps the column check, limits, lanes and the browser revert.

```php
use Illuminate\Database\Eloquent\Model;
use Relaticle\Flowforge\Exceptions\CardMoveRejectedException;

protected function authorizeCardMove(Model $card, string $targetColumnId, ?string $toLaneId = null): void
{
    if (! auth()->user()->can('update', $card)) {
        throw CardMoveRejectedException::because('You cannot move this card.');
    }
}

protected function persistCardMove(Model $card, mixed $columnValue, string $position): void
{
    parent::persistCardMove($card, $columnValue, $position);

    if ($columnValue === 'completed') {
        $card->update(['completed_at' => now()]);
    }
}
```

`persistCardMove()` runs inside the move's database transaction. Throw `CardMoveRejectedException::because()` from either hook to refuse with a toast and revert the card. An `abort(403)` reverts the card but shows Livewire's error UI.

## Artisan Commands

### Generate Board

```bash
php artisan flowforge:make-board TaskBoard
php artisan flowforge:make-board TaskBoard --resource  # For resource page
```

### Diagnose Position Issues

```bash
php artisan flowforge:diagnose-positions "App\Models\Task" status position
```

Checks for:
- Missing positions (NULL values)
- Duplicate positions within columns
- Position inversions
- Gaps too small for further insertions

### Rebalance Positions

```bash
php artisan flowforge:rebalance-positions "App\Models\Task" status position
php artisan flowforge:rebalance-positions "App\Models\Task" status position --column=in_progress
```

### Interactive Repair

```bash
php artisan flowforge:repair-positions "App\Models\Task" status position
```

Offers multiple repair strategies:
- Fill NULL positions
- Fix duplicates
- Rebalance specific columns
- Full rebalance

## Configuration

Publish config:

```bash
php artisan vendor:publish --tag=flowforge-config
```

`config/flowforge.php`:

```php
return [
    'columns' => [
        'default_limit' => 50,
    ],
    'kanban' => [
        'initial_cards_count' => 20,
        'cards_increment' => 10,
    ],
    'ui' => [
        'show_item_counts' => true,
    ],
];
```

## Migration Pattern

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->flowforgePositionColumn();
            $table->unique(['status', 'position']);
        });
    }
};
```

**Important:** The unique constraint on `[column_identifier, position]` is required for concurrent safety.

## Common Patterns

### Scoped Boards (Multi-tenancy)

```php
public function board(Board $board): Board
{
    return $board
        ->query(Task::query()->where('team_id', auth()->user()->team_id))
        // ...
}
```

### Dynamic Columns from Database

```php
public function board(Board $board): Board
{
    $statuses = Status::ordered()->get();

    return $board
        ->query(Task::query())
        ->columnIdentifier('status_id')
        ->positionIdentifier('position')
        ->columns(
            $statuses->map(fn ($status) =>
                Column::make($status->id, $status->name)
                    ->icon($status->icon)
                    ->color($status->color)
            )->toArray()
        );
}
```

### Eager Loading for Cards

```php
public function board(Board $board): Board
{
    return $board
        ->query(Task::query()->with(['assignee', 'tags', 'project']))
        // ...
}
```

### Custom Card Click Behavior

```php
->recordActions([
    Action::make('view')
        ->url(fn ($record) => TaskResource::getUrl('view', ['record' => $record]))
        ->openUrlInNewTab(),
])
```

## Requirements

- PHP 8.3+ with `ext-bcmath`
- Laravel 12+
- Filament 5.x
- Position column: `DECIMAL(20,10)` with unique constraint
