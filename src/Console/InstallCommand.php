<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'csatf:baseline:install';

    protected $description = 'Publish the CSATF app-baseline config and print the wiring steps.';

    public function handle(): int
    {
        $this->callSilent('vendor:publish', ['--tag' => 'csatf-baseline-config']);
        $this->components->info('Published config/csatf-baseline.php');

        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Register the admin resolver in a service provider boot():');
        $this->line('       use Csatf\\LaravelBaseline\\Facades\\Baseline;');
        $this->line('       Baseline::authorizeAdminUsing(fn ($user) => $user->is_admin);');
        $this->line('     ...or set CSATF_ADMIN_EMAILS=a@csatf.org,b@csatf.org for the email fallback.');
        $this->line('     (With neither set, dashboard access is denied — fail closed.)');
        $this->line('  2. Remove any per-app viewPulse / viewTelescope / viewApiDocs gate definitions.');
        $this->line('  3. Set the DB connections that /up should verify:');
        $this->line('       CSATF_HEALTH_CONNECTIONS=pgsql,redshift   (defaults to the default connection)');
        $this->line('  4. Add `php artisan csatf:version:stamp` to your deploy script; render @appVersion in a view.');
        $this->line('  5. (Optional) JSON error envelope — in bootstrap/app.php withExceptions():');
        $this->line('       \\Csatf\\LaravelBaseline\\Support\\ApiExceptions::register($exceptions);');

        return self::SUCCESS;
    }
}
