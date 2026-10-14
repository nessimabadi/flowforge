<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Components;

use Closure;
use Filament\Infolists\Components\Entry;
use Filament\Models\Contracts\HasAvatar;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CardAvatar extends Entry implements HasEmbeddedView
{
    protected int $limit = 3;

    protected ?Closure $nameUsing = null;

    protected ?Closure $avatarUrlUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function nameUsing(?Closure $callback): static
    {
        $this->nameUsing = $callback;

        return $this;
    }

    public function avatarUrlUsing(?Closure $callback): static
    {
        $this->avatarUrlUsing = $callback;

        return $this;
    }

    /**
     * @return array<int, array{name: string, url: ?string, initials: string}>
     */
    public function getPeople(): array
    {
        $state = $this->getState();

        $models = match (true) {
            $state instanceof Collection => $state->all(),
            is_array($state) => $state,
            default => [$state],
        };

        return collect($models)
            ->filter(fn (mixed $person): bool => $person instanceof Model)
            ->map(fn (Model $person): array => $this->describePerson($person))
            ->values()
            ->all();
    }

    public function isHidden(): bool
    {
        return parent::isHidden() || $this->getPeople() === [];
    }

    public function toEmbeddedHtml(): string
    {
        $people = $this->getPeople();

        if ($people === []) {
            return '';
        }

        return view('flowforge::components.card-avatar', [
            'visible' => array_slice($people, 0, $this->limit),
            'overflow' => max(0, count($people) - $this->limit),
            'names' => implode(', ', array_column($people, 'name')),
        ])->render();
    }

    /**
     * @return array{name: string, url: ?string, initials: string}
     */
    protected function describePerson(Model $person): array
    {
        $name = (string) ($this->nameUsing
            ? $this->evaluate($this->nameUsing, ['person' => $person], $this->injectionsFor($person))
            : ($person->getAttribute('name') ?? $person->getKey()));

        $url = $this->avatarUrlUsing
            ? $this->evaluate($this->avatarUrlUsing, ['person' => $person], $this->injectionsFor($person))
            : ($person instanceof HasAvatar ? $person->getFilamentAvatarUrl() : null);

        $initials = Str::of($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return ['name' => $name, 'url' => filled($url) ? (string) $url : null, 'initials' => $initials];
    }

    /**
     * @return array<class-string, Model>
     */
    protected function injectionsFor(Model $person): array
    {
        return [
            Model::class => $person,
            $person::class => $person,
        ];
    }
}
