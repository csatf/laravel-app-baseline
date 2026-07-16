<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline\Tests\Feature;

use Csatf\LaravelBaseline\Facades\Baseline;
use Csatf\LaravelBaseline\LaravelBaselineServiceProvider;
use Csatf\LaravelBaseline\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Mimics Laravel Pulse/Telescope: defines `viewPulse` in boot(), booting after
 * the baseline provider. If the baseline registered its gates in boot() rather
 * than a booted() callback, this provider would win and lock admins out.
 */
class CompetingDashboardProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('viewPulse', fn ($user = null): bool => false);
    }
}

class GateOrderingTest extends TestCase
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelBaselineServiceProvider::class,
            CompetingDashboardProvider::class,
        ];
    }

    public function test_baseline_gate_wins_over_a_later_booting_provider(): void
    {
        Baseline::authorizeAdminUsing(fn ($user): bool => true);

        $this->assertTrue(
            Gate::forUser((object) ['email' => 'admin@csatf.org'])->allows('viewPulse')
        );
    }
}
