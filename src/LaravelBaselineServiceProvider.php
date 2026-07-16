<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline;

use Csatf\LaravelBaseline\Console\InstallCommand;
use Csatf\LaravelBaseline\Console\StampVersionCommand;
use Csatf\LaravelBaseline\Http\Middleware\SecurityHeaders;
use Csatf\LaravelBaseline\Listeners\CheckDatabaseConnections;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class LaravelBaselineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/csatf-baseline.php', 'csatf-baseline');

        $this->app->singleton(Baseline::class, static fn (): Baseline => new Baseline);
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

        // Defer gate definitions until every provider has booted, so the
        // baseline's admin gates win over dashboard packages (Pulse, Telescope)
        // that define their own viewPulse/viewTelescope defaults during boot().
        $this->app->booted(fn () => $this->registerDashboardGates());
        $this->registerSecurityHeaders();
        $this->registerHealthChecks();

        Blade::directive(
            'appVersion',
            static fn (): string => '<?php echo e(\\Csatf\\LaravelBaseline\\Support\\AppVersion::current()); ?>'
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

    /**
     * Hook database-connectivity checks into Laravel's built-in `/up` endpoint
     * via the DiagnosingHealth event, rather than registering a separate route.
     */
    protected function registerHealthChecks(): void
    {
        if (! config('csatf-baseline.health.enabled', true)) {
            return;
        }

        Event::listen(DiagnosingHealth::class, CheckDatabaseConnections::class);
    }
}
