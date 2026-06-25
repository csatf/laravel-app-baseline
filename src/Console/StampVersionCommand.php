<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline\Console;

use Csatf\LaravelBaseline\Support\AppVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class StampVersionCommand extends Command
{
    protected $signature = 'csatf:version:stamp';

    protected $description = 'Write the current git version to the deploy stamp file (run during deploy).';

    public function handle(): int
    {
        $path = (string) config('csatf-baseline.version.stamp_path', storage_path('app/version'));

        $exact = $this->git(['describe', '--tags', '--exact-match']);
        $nearest = $this->git(['describe', '--tags', '--abbrev=0']);
        $sha = $this->git(['rev-parse', '--short', 'HEAD']);

        $formatted = AppVersion::format($exact ?? $nearest, $exact !== null, $sha);

        if ($formatted === null) {
            if (is_file($path)) {
                @unlink($path);
            }
            $this->components->warn('No git tag found; cleared the version stamp.');

            return self::SUCCESS;
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        file_put_contents($path, $formatted.PHP_EOL);
        $this->components->info("Stamped version: {$formatted}");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $args
     */
    protected function git(array $args): ?string
    {
        $result = Process::path(base_path())->run(array_merge(['git'], $args));

        if (! $result->successful()) {
            return null;
        }

        $output = trim($result->output());

        return $output === '' ? null : $output;
    }
}
