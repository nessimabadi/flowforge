<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Relaticle\Flowforge\Database\Factories\UserFactory as BaseUserFactory;

class UserFactory extends BaseUserFactory
{
    protected $model = User::class;
}
