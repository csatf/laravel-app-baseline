<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline\Http\Controllers;

use Csatf\LaravelBaseline\Baseline;
use Illuminate\Http\JsonResponse;
use Throwable;

class HealthController
{
    public function __invoke(Baseline $baseline): JsonResponse
    {
        $checks = [];
        $healthy = true;

        foreach ($baseline->healthChecks() as $name => $check) {
            try {
                $check();
                $checks[$name] = 'ok';
            } catch (Throwable $e) {
                $healthy = false;
                $checks[$name] = config('app.debug') ? 'error: '.$e->getMessage() : 'error';
            }
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'error',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
