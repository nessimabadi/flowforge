<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Components;

use Filament\Schemas\Components\Component;

class CardTitle extends Component
{
    protected string $view = 'flowforge::components.card-title';

    public static function make(): static
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }
}
