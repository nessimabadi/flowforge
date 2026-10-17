<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

class PlainEnumTask extends Task
{
    protected $table = 'tasks';

    protected $casts = ['status' => PlainStatusEnum::class];
}
