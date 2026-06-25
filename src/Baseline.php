<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline;

class Baseline
{
    /** @var (callable(mixed): bool)|null */
    protected $adminResolver = null;

    /** @var array<string, callable(): mixed> */
    protected array $healthChecks = [];

    /**
     * Register the callback that decides whether a user may access protected
     * dashboards (Pulse, Telescope, API docs). Call from a service provider's
     * boot() method.
     *
     * @param  callable(mixed): bool  $resolver
     */
    public function authorizeAdminUsing(callable $resolver): void
    {
        $this->adminResolver = $resolver;
    }

    /**
     * Decide whether the given user is an admin. Uses the registered resolver
     * if present, otherwise the config email allowlist, otherwise denies
     * (fail closed).
     */
    public function isAdmin(mixed $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->adminResolver !== null) {
            return (bool) ($this->adminResolver)($user);
        }

        $emails = array_filter((array) config('csatf-baseline.admin.emails', []));

        return $emails !== [] && in_array($user->email ?? null, $emails, true);
    }

    /**
     * Register a named health check. The callback should throw on failure.
     *
     * @param  callable(): mixed  $check
     */
    public function registerHealthCheck(string $name, callable $check): void
    {
        $this->healthChecks[$name] = $check;
    }

    /**
     * @return array<string, callable(): mixed>
     */
    public function healthChecks(): array
    {
        return $this->healthChecks;
    }
}
