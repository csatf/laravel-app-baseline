<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline\Facades;

use Csatf\LaravelBaseline\Baseline as BaselineManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void authorizeAdminUsing(callable $resolver)
 * @method static bool isAdmin(mixed $user)
 * @method static void registerHealthCheck(string $name, callable $check)
 * @method static array<string, callable(): mixed> healthChecks()
 *
 * @see \Csatf\LaravelBaseline\Baseline
 */
class Baseline extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaselineManager::class;
    }
}
