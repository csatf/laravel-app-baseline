<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline\Support;

class AppVersion
{
    /**
     * Resolve the running application version without invoking the git binary:
     *   1. the deploy stamp file (written by `csatf:version:stamp`)
     *   2. config('app.version') / APP_VERSION
     *   3. the short commit SHA read directly from .git/HEAD
     *   4. 'dev'
     */
    public static function current(): string
    {
        $path = (string) config('csatf-baseline.version.stamp_path', storage_path('app/version'));
        if ($path !== '' && is_file($path)) {
            $stamped = trim((string) @file_get_contents($path));
            if ($stamped !== '') {
                return $stamped;
            }
        }

        $configured = config('app.version');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $git = self::fromGitHead(base_path('.git'));
        if ($git !== null) {
            return $git;
        }

        return 'dev';
    }

    /**
     * Format a `git describe` result into a display string:
     *   exact tag     -> "1.2.0"
     *   tag + commits -> "1.2.0 (a3b9c09)"
     *   no tag        -> null
     */
    public static function format(?string $tag, bool $onExactTag, ?string $sha): ?string
    {
        $tag = trim((string) $tag);
        if ($tag === '') {
            return null;
        }

        $semver = ltrim($tag, 'vV');
        $sha = trim((string) $sha);

        if ($onExactTag || $sha === '') {
            return $semver;
        }

        return "{$semver} ({$sha})";
    }

    protected static function fromGitHead(string $gitDir): ?string
    {
        $headFile = $gitDir.'/HEAD';
        if (! is_file($headFile)) {
            return null;
        }

        $head = trim((string) @file_get_contents($headFile));
        if ($head === '') {
            return null;
        }

        if (! str_starts_with($head, 'ref:')) {
            return substr($head, 0, 7); // detached HEAD
        }

        $ref = trim(substr($head, 4));

        $refFile = $gitDir.'/'.$ref;
        if (is_file($refFile)) {
            $sha = trim((string) @file_get_contents($refFile));

            return $sha !== '' ? substr($sha, 0, 7) : null;
        }

        // Fall back to packed-refs for repos that have been gc'd.
        $packed = $gitDir.'/packed-refs';
        if (is_file($packed)) {
            foreach (file($packed, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                if ($line === '' || $line[0] === '#' || $line[0] === '^') {
                    continue;
                }

                $parts = explode(' ', $line, 2);
                if (count($parts) === 2 && $parts[1] === $ref) {
                    return substr($parts[0], 0, 7);
                }
            }
        }

        return null;
    }
}
