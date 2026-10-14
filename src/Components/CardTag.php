<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Components;

use Filament\Infolists\Components\TextEntry;
use Relaticle\Flowforge\Support\ColorResolver;

use function Filament\Support\get_color_css_variables;

class CardTag extends TextEntry
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();
    }

    public function toEmbeddedHtml(): string
    {
        $state = $this->getState();

        if (blank($state)) {
            return '';
        }

        $color = ColorResolver::resolve($this->getColor($state)) ?? 'gray';

        return view('flowforge::components.card-tag', [
            'label' => $this->formatState($state),
            'colorStyle' => get_color_css_variables($color, shades: [500]),
        ])->render();
    }
}
