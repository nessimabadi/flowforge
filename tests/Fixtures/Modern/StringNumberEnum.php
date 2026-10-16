<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Filament\Support\Contracts\HasLabel;

enum StringNumberEnum: string implements HasLabel
{
    case One = '1';
    case Two = '2';

    public function getLabel(): string
    {
        return match ($this) {
            self::One => 'Tier one',
            self::Two => 'Tier two',
        };
    }
}
