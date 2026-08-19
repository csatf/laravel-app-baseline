# csatf/laravel-app-baseline

The runtime baseline every CSATF Laravel app installs: the cross-cutting concerns
that *every* app should have, behind one dependency and one config file. Pairs
with `csatf/laravel-devtools` (dev tooling) — this one ships to production.

Everything is config-gated and degrades safely; nothing changes response shapes
unless you opt in.

## Requirements

PHP 8.2+, Laravel 11 / 12 / 13.

## Install

```json
"repositories": [
    { "type": "vcs", "url": "git@github.com:csatf/laravel-app-baseline.git" }
]
```

```sh
composer require csatf/laravel-app-baseline
php artisan csatf:baseline:install
```

The install command publishes `config/csatf-baseline.php` and prints the wiring
steps below.

## What it provides

### 1. Security headers
`SecurityHeaders` middleware is appended to the global stack automatically (so it
also covers Filament), emitting `X-Content-Type-Options`, `X-Frame-Options`,
`Referrer-Policy`, `Permissions-Policy`, and — on an `https` `app.url` — HSTS.
HSTS defaults to `max-age=31536000; includeSubDomains` (one year). Tune with
`CSATF_HSTS_MAX_AGE`, and drop the sub-domain directive with
`CSATF_HSTS_INCLUDE_SUBDOMAINS=false` if a sub-domain can't serve HTTPS. CSP is
left `null` by default; set `CSATF_CSP` (report-only first) per app. Disable
security headers entirely with `CSATF_SECURITY_HEADERS=false`.

### 2. App version
`Csatf\LaravelBaseline\Support\AppVersion::current()` resolves the running version
without shelling out to git: deploy stamp file → `APP_VERSION` → `.git/HEAD` →
`"dev"`. Add `php artisan csatf:version:stamp` to your deploy script (it writes
`storage/app/version` from `git describe`), and render `@appVersion` anywhere in a
Blade view.

### 3. Dashboard admin gates
Defines `viewPulse` / `viewTelescope` / `viewApiDocs` (and optionally
`viewHorizon`), all delegating to **one** admin decision so access is consistent
across apps. Register the resolver in a service provider's `boot()`:

```php
use Csatf\LaravelBaseline\Facades\Baseline;

Baseline::authorizeAdminUsing(fn ($user) => $user->is_admin);          // compliance-api
// Baseline::authorizeAdminUsing(fn ($user) => $user->hasRole('IT'));  // stp-docs
```

Or, for the simple case, set `CSATF_ADMIN_EMAILS=a@csatf.org,b@csatf.org`. **With
neither configured, access is denied (fail closed)** — this replaces per-app gates
like a hard-coded admin email or a `viewApiDocs => true`. Remove those per-app
definitions when adopting.

### 4. Database health on `/up`
Rather than add a parallel endpoint, this hooks Laravel's built-in `/up` health
check: a `DiagnosingHealth` listener verifies the configured database connections,
so `/up` returns **200** when they're reachable and **non-200** when they're not.
Name the connections per app (empty = the default connection):

```
CSATF_HEALTH_CONNECTIONS=pgsql,redshift
```

`/up` is already wired in every app's `bootstrap/app.php`, so there's no new
route. Disable the DB check with `CSATF_HEALTH_DB_CHECK=false` (back to a plain
boots-only `/up`).

> Each `/up` hit opens a real connection, and Redshift connects are heavier than
> Postgres — point uptime monitors at it on the order of 30–60s, not every second.
> (This makes `/up` a dependency check, not a bare liveness probe; that's the right
> call for the Forge/Vapor apps, where `/up` is polled by monitors rather than used
> as a Kubernetes liveness probe.)

### 5. JSON exception envelope (opt-in)
Off by default because it changes response shapes. Turn it on in
`bootstrap/app.php` to get a consistent `{ "message", "errors"? }` shape for JSON
requests (web requests are untouched):

```php
->withExceptions(function (Illuminate\Foundation\Configuration\Exceptions $exceptions) {
    Csatf\LaravelBaseline\Support\ApiExceptions::register($exceptions);
})
```

## Configuration

`config/csatf-baseline.php` keys: `security_headers`, `version`, `admin`,
`dashboards`, `health`. All env-overridable; the admin resolver is registered in
code (kept out of config so `config:cache` stays closure-free).

## Per-app adoption notes

| App | Admin resolver | Health connections |
|-----|----------------|--------------------|
| compliance-api | `fn ($u) => $u->is_admin` | `pgsql,redshift` |
| completions | `CSATF_ADMIN_EMAILS` | `pgsql,redshift,redshift_ingest` |
| stp-docs | `fn ($u) => $u->hasAnyRole([...])` | `pgsql` (also drop its own SecurityHeaders/AppVersion) |
| team | `fn ($u) => $u->type === UserType::ADMIN` | `pgsql` |

## Versioning

SemVer, tag-driven. Because this loads in production across every app, breaking
changes to config keys or the public API bump the major. Pin with `^1.0`.
