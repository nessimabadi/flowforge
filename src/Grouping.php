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

class Grouping extends Component
{
    public const EMPTY_LANE = '__flowforge_empty';

    protected string $evaluationIdentifier = 'grouping';

    protected string | Htmlable | Closure | null $label = null;

    protected ?Closure $lanes = null;

    protected string | Closure | null $emptyLaneLabel = null;

    protected ?Closure $emptyLaneQuery = null;

    protected ?Closure $moveUsing = null;

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

        return static::make($column)
            ->lanes(fn (Builder $boardQuery): array => static::attributeLanes($boardQuery, $attribute, $column))
            ->emptyLane(
                fn (): string => __('flowforge::flowforge.no_value', ['attribute' => Str::lower(Str::headline($column))]),
                fn (Builder $query): Builder => $query->whereNull($attribute),
            )
            ->moveUsing(fn (Model $card, Lane $to): bool => $card->fill([
                $column => $to->getName() === self::EMPTY_LANE ? null : $to->getName(),
            ])->save());
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

    public function isWritable(): bool
    {
        return $this->moveUsing !== null;
    }

    /**
     * @return array<int, Lane>
     */
    public function getLanes(Builder $boardQuery): array
    {
        $lanes = $this->lanes === null
            ? []
            : $this->evaluate($this->lanes, ['boardQuery' => clone $boardQuery], [Builder::class => clone $boardQuery]);

        if ($this->emptyLaneQuery !== null) {
            $lanes[] = Lane::make(self::EMPTY_LANE)
                ->label($this->evaluate($this->emptyLaneLabel))
                ->query($this->emptyLaneQuery);
        }

        return array_values($lanes);
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
        $this->evaluate($this->moveUsing, ['card' => $card, 'from' => $from, 'to' => $to], [Model::class => $card]);
    }

    /**
     * @return array<int, Lane>
     */
    protected static function attributeLanes(Builder $boardQuery, string $attribute, string $column): array
    {
        $enum = $boardQuery->getModel()->getCasts()[$column] ?? null;
        $enum = is_string($enum) && enum_exists($enum) && is_subclass_of($enum, BackedEnum::class) ? $enum : null;

        return $boardQuery
            ->reorder()
            ->whereNotNull($attribute)
            ->select($attribute)
            ->selectRaw('count(*) as flowforge_lane_total')
            ->groupBy($attribute)
            ->orderByDesc('flowforge_lane_total')
            ->orderBy($attribute)
            ->toBase()
            ->get()
            ->map(function (object $row) use ($attribute, $column, $enum): Lane {
                $value = $row->{$column};
                $case = $enum ? $enum::tryFrom($value) : null;

                return Lane::make((string) $value)
                    ->label($case instanceof HasLabel ? $case->getLabel() : Str::headline((string) $value))
                    ->color($case instanceof HasColorContract ? $case->getColor() : null)
                    ->query(fn (Builder $query): Builder => $query->where($attribute, $value));
            })
            ->all();
    }
}
