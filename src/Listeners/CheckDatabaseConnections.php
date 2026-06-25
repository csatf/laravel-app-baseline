<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline\Listeners;

use Illuminate\Support\Facades\DB;

/**
 * Verifies the configured database connections during Laravel's built-in `/up`
 * health check. `/up` dispatches DiagnosingHealth; throwing from a listener
 * makes `/up` respond non-200, so this turns `/up` into a real DB-connectivity
 * check (the default connection, plus e.g. redshift) without a separate route.
 */
class CheckDatabaseConnections
{
    public function handle(): void
    {
        $connections = array_filter((array) config('csatf-baseline.health.connections', []));

        if ($connections === []) {
            $default = config('database.default');
            if (is_string($default) && $default !== '') {
                $connections = [$default];
            }
        }

        foreach ($connections as $name) {
            // getPdo() opens a real connection and throws if it can't connect.
            DB::connection((string) $name)->getPdo();
        }
    }
}
