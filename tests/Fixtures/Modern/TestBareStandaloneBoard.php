<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

class TestBareStandaloneBoard extends TestStandaloneBoard
{
    public function render(): string
    {
        return '{{ $this->board }}';
    }
}
