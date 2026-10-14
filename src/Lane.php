<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Closure;
use Filament\Support\Components\Component;
use Filament\Support\Concerns\HasColor;
use Filament\Support\Concerns\HasIcon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Lane extends Component
{
    use HasColor;
    use HasIcon;

    protected string $evaluationIdentifier = 'lane';

    protected string | Htmlable | Closure | null $label = null;

    protected string | Closure | null $avatar = null;

    protected ?Closure $query = null;

    final public function __construct(protected string $name) {}

    public static function make(string | int $name): static
    {
        $static = app(static::class, ['name' => (string) $name]);
        $static->configure();

        return $static;
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

    public function avatar(string | Closure | null $url): static
    {
        $this->avatar = $url;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->evaluate($this->avatar);
    }

    public function query(?Closure $callback): static
    {
        $this->query = $callback;

        return $this;
    }

    public function applyQuery(Builder $query): Builder
    {
        if ($this->query === null) {
            return $query;
        }

        return $this->evaluate($this->query, ['query' => $query], [Builder::class => $query]) ?? $query;
    }
}
