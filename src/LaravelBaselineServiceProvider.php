<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline;

use Csatf\LaravelBaseline\Console\InstallCommand;
use Csatf\LaravelBaseline\Console\StampVersionCommand;
use Csatf\LaravelBaseline\Http\Controllers\HealthController;
use Csatf\LaravelBaseline\Http\Middleware\SecurityHeaders;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelBaselineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/csatf-baseline.php', 'csatf-baseline');

        $this->app->singleton(Baseline::class, static fn (): Baseline => new Baseline());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/csatf-baseline.php' => config_path('csatf-baseline.php'),
        ], 'csatf-baseline-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                StampVersionCommand::class,
            ]);
        }

        $this->registerDashboardGates();
        $this->registerSecurityHeaders();
        $this->registerHealthRoute();

        Blade::directive(
            'appVersion',
            static fn (): string => "<?php echo e(\\Csatf\\LaravelBaseline\\Support\\AppVersion::current()); ?>"
        );
    }

    protected function registerDashboardGates(): void
    {
        $baseline = $this->app->make(Baseline::class);

        foreach ((array) config('csatf-baseline.dashboards', []) as $ability => $enabled) {
            if ($enabled) {
                Gate::define((string) $ability, static fn ($user = null): bool => $baseline->isAdmin($user));
            }
        }
    }

    protected function registerSecurityHeaders(): void
    {
        if (! config('csatf-baseline.security_headers.enabled', true)) {
            return;
        }

        $this->app->booted(function (): void {
            if ($this->app->runningInConsole()) {
                return;
            }

            $this->app->make(Kernel::class)->pushMiddleware(SecurityHeaders::class);
        });
    }

    protected function registerHealthRoute(): void
    {
        if (! config('csatf-baseline.health.enabled', true)) {
            return;
        }

        Route::middleware((array) config('csatf-baseline.health.middleware', []))
            ->get((string) config('csatf-baseline.health.route', '/health'), HealthController::class);
    }
}
