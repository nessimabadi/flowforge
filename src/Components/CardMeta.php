<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Components;

use Filament\Infolists\Components\Entry;
use Filament\Schemas\Schema;

class CardMeta extends CardFlex
{
    protected string $gap = 'sm';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extraAttributes(['class' => 'ff-card-meta']);
        $this->grow(false);
    }

    public function getDefaultChildComponents(): array | Schema
    {
        $children = parent::getDefaultChildComponents();
        $components = $children instanceof Schema ? $children->getComponents() : $children;

        foreach ($components as $component) {
            if ($component instanceof Entry) {
                $component->hiddenLabel();
            }
        }

        return $children;
    }
}
