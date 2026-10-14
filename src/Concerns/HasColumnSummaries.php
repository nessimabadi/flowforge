<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Column;

trait HasColumnSummaries
{
    protected ?Closure $columnSummary = null;

    public function columnSummary(?Closure $callback): static
    {
        $this->columnSummary = $callback;

        return $this;
    }

    public function getColumnSummary(Column $column): ?string
    {
        if ($this->columnSummary === null) {
            return null;
        }

        $columnQuery = $this->getFilteredColumnQuery($column->getName());

        if ($columnQuery === null) {
            return null;
        }

        $summary = $this->evaluate(
            $this->columnSummary,
            namedInjections: ['query' => $columnQuery, 'columnQuery' => $columnQuery, 'column' => $column],
            typedInjections: [
                BuilderContract::class => $columnQuery,
                Builder::class => $columnQuery,
                $columnQuery::class => $columnQuery,
                Column::class => $column,
            ],
        );

        return filled($summary) ? (string) $summary : null;
    }
}
