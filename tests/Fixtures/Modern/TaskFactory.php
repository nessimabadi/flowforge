<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Relaticle\Flowforge\Database\Factories\TaskFactory as BaseTaskFactory;

class TaskFactory extends BaseTaskFactory
{
    protected $model = Task::class;
}
