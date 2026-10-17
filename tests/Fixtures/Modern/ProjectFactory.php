<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Relaticle\Flowforge\Database\Factories\ProjectFactory as BaseProjectFactory;

class ProjectFactory extends BaseProjectFactory
{
    protected $model = Project::class;
}
