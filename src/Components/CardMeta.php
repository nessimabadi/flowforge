<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Components;

class CardMeta extends CardFlex
{
    protected string $gap = 'sm';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extraAttributes(['class' => 'ff-card-meta']);
    }
}
