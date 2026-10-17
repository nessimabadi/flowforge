<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Filament\Actions\Action;

class TestCardActionDefaultUrlBoard extends TestCardActionBoard
{
    public function getDefaultActionUrl(Action $action): ?string
    {
        return 'https://example.test/default-action-url';
    }

    protected function cardActionName(): string
    {
        return 'run';
    }
}
