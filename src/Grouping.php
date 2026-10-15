<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use BackedEnum;
use Closure;
use Filament\Support\Components\Component;
use Filament\Support\Contracts\HasColor as HasColorContract;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionEnum;
use Relaticle\Flowforge\Exceptions\CardMoveRejectedException;
use Throwable;

class Grouping extends Component
{
    public const EMPTY_LANE = '__flowforge_empty';

    protected string $evaluationIdentifier = 'grouping';

    protected string | Htmlable | Closure | null $label = null;

    protected ?Closure $lanes = null;

    protected string | Closure | null $emptyLaneLabel = null;

    protected ?Closure $emptyLaneQuery = null;

    protected ?Closure $moveUsing = null;

    protected ?string $writeQualifier = null;

    protected ?string $boardTable = null;

    /**
     * @var array<string, bool>
     */
    protected static array $textColumns = [];

    final public function __construct(protected string $name) {}

    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }

    public static function attribute(string $attribute): static
    {
        $column = Str::afterLast($attribute, '.');

        $grouping = static::make($column)
            ->lanes(fn (Builder $boardQuery): array => static::attributeLanes($boardQuery, $attribute, $column))
            ->emptyLane(
                fn (Grouping $grouping): string => __('flowforge::flowforge.no_value', ['attribute' => Str::lower((string) $grouping->getLabel())]),
                fn (Builder $query): Builder => static::isTextColumn($query->getModel(), $attribute)
                    ? $query->where(fn (Builder $nested): Builder => $nested->whereNull($attribute)->orWhere($attribute, ''))
                    : $query->whereNull($attribute),
            )
            ->moveUsing(function (Model $card, Lane $to) use ($column): void {
                $saved = $card->forceFill([$column => static::writeValue($card, $column, $to)])->save();

                if (! $saved) {
                    throw CardMoveRejectedException::because(__('flowforge::flowforge.lane_write_failed'));
                }
            });

        $grouping->writeQualifier = str_contains($attribute, '.') ? Str::beforeLast($attribute, '.') : null;

        return $grouping;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function label(string | Htmlable | Closure | null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string | Htmlable
    {
        return $this->evaluate($this->label) ?? Str::headline($this->name);
    }

    public function lanes(?Closure $callback): static
    {
        $this->lanes = $callback;

        return $this;
    }

    public function emptyLane(string | Closure $label, Closure $query): static
    {
        $this->emptyLaneLabel = $label;
        $this->emptyLaneQuery = $query;

        return $this;
    }

    public function moveUsing(?Closure $callback): static
    {
        $this->moveUsing = $callback;

        return $this;
    }

    public function boardTable(?string $table): static
    {
        $this->boardTable = $table;

        return $this;
    }

    public function isWritable(): bool
    {
        if ($this->moveUsing === null) {
            return false;
        }

        return $this->writeQualifier === null
            || $this->boardTable === null
            || $this->writeQualifier === $this->boardTable;
    }

    /**
     * @return array<int, Lane>
     */
    public function getLanes(Builder $boardQuery): array
    {
        $lanes = $this->lanes === null
            ? []
            : collect($this->evaluate($this->lanes, ['boardQuery' => clone $boardQuery], [Builder::class => clone $boardQuery]))->values()->all();

        if ($this->emptyLaneQuery !== null) {
            $lanes[] = Lane::make(self::EMPTY_LANE)
                ->label($this->evaluate($this->emptyLaneLabel, ['grouping' => $this]))
                ->query($this->emptyLaneQuery);
        }

        return $lanes;
    }

    public function findLane(?string $name, Builder $boardQuery): ?Lane
    {
        foreach ($this->getLanes($boardQuery) as $lane) {
            if ($lane->getName() === $name) {
                return $lane;
            }
        }

        return null;
    }

    public function moveCard(Model $card, Lane $from, Lane $to): void
    {
        $this->evaluate(
            $this->moveUsing,
            ['card' => $card, 'record' => $card, 'from' => $from, 'to' => $to],
            [Model::class => $card, $card::class => $card],
        );
    }

    /**
     * @return array<int, Lane>
     */
    protected static function attributeLanes(Builder $boardQuery, string $attribute, string $column): array
    {
        $model = $boardQuery->getModel();
        $enum = static::enumCast($model, $column);
        $query = $boardQuery->toBase();

        $query->reorder();
        $query->groups = null;
        $query->havings = null;
        $query->bindings['groupBy'] = [];
        $query->bindings['having'] = [];
        $query->distinct = false;
        $query->limit = null;
        $query->offset = null;

        $query->whereNotNull($attribute);

        if (static::isTextColumn($model, $attribute)) {
            $query->where($attribute, '!=', '');
        }

        return $query
            ->select($attribute)
            ->selectRaw('count(*) as flowforge_lane_total')
            ->groupBy($attribute)
            ->orderByDesc('flowforge_lane_total')
            ->orderBy($attribute)
            ->get()
            ->map(function (object $row) use ($attribute, $column, $enum): Lane {
                $value = $row->{$column};
                $name = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
                $backing = $enum ? static::coerceToBacking($enum, $value) : null;
                $case = $backing === null ? null : $enum::tryFrom($backing);

                return Lane::make($name)
                    ->label($case instanceof HasLabel ? $case->getLabel() : Lane::defaultLabel($name))
                    ->color($case instanceof HasColorContract ? $case->getColor() : null)
                    ->query(fn (Builder $query): Builder => $query->where($attribute, $value));
            })
            ->all();
    }

    /**
     * @return class-string<BackedEnum>|null
     */
    protected static function enumCast(Model $model, string $column): ?string
    {
        $cast = $model->getCasts()[$column] ?? null;

        return is_string($cast) && enum_exists($cast) && is_subclass_of($cast, BackedEnum::class) ? $cast : null;
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     */
    protected static function coerceToBacking(string $enum, mixed $value): int | string | null
    {
        $type = (new ReflectionEnum($enum))->getBackingType()?->getName();

        if ($type === 'int') {
            return is_numeric($value) ? (int) $value : null;
        }

        return (string) $value;
    }

    protected static function writeValue(Model $card, string $column, Lane $to): bool | string | null
    {
        if ($to->getName() === self::EMPTY_LANE) {
            return null;
        }

        if (in_array(strtolower((string) ($card->getCasts()[$column] ?? '')), ['bool', 'boolean'], true)) {
            return $to->getName() === '1';
        }

        return $to->getName();
    }

    protected static function isTextColumn(Model $model, string $attribute): bool
    {
        $table = str_contains($attribute, '.') ? Str::beforeLast($attribute, '.') : $model->getTable();
        $column = Str::afterLast($attribute, '.');
        $key = "{$model->getConnectionName()}.{$table}.{$column}";

        return static::$textColumns[$key] ??= static::columnIsText($model, $table, $column);
    }

    protected static function columnIsText(Model $model, string $table, string $column): bool
    {
        try {
            $columns = $model->getConnection()->getSchemaBuilder()->getColumns($table);
        } catch (Throwable) {
            return false;
        }

        foreach ($columns as $definition) {
            if ($definition['name'] === $column) {
                return in_array(strtolower($definition['type_name']), ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'string', 'bpchar', 'nvarchar', 'nchar', 'citext'], true);
            }
        }

        return false;
    }
}
