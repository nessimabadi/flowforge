<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures\Modern;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CastTask extends Task
{
    public static bool $orderScope = false;

    public static bool $vetoSaves = false;

    public static mixed $written = null;

    protected $fillable = [
        'title',
        'status',
        'order_position',
    ];

    protected $withCount = ['assignees'];

    protected $casts = [
        'priority' => IntBackedEnum::class,
        'description' => StatusEnum::class,
        'estimated_hours' => StringNumberEnum::class,
        'actual_hours' => 'boolean',
    ];

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('flowforge-order', function (Builder $builder): void {
            if (static::$orderScope) {
                $builder->orderBy('title');
            }
        });

        static::saving(function (CastTask $task): bool {
            static::$written = $task->getAttributes()['actual_hours'] ?? null;

            return ! static::$vetoSaves;
        });
    }
}
