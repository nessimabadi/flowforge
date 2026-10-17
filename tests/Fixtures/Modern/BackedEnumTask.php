<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

class BackedEnumTask extends Task
{
    protected $table = 'tasks';

    protected $casts = ['status' => StatusEnum::class];
}
