# csatf/laravel-app-baseline

The runtime baseline every CSATF Laravel app installs: the cross-cutting concerns
that *every* app should have, behind one dependency and one config file. Pairs
with `csatf/laravel-devtools` (dev tooling) — this one ships to production.

Everything is config-gated and degrades safely; nothing changes response shapes
unless you opt in.

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
`Referrer-Policy`, `Permissions-Policy`, and — on an `https` `app.url` — HSTS. CSP
is left `null` by default; set `CSATF_CSP` (report-only first) per app. Disable
entirely with `CSATF_SECURITY_HEADERS=false`.

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

### 4. Health endpoint
A `GET /health` route aggregates named checks to `{ "status", "checks" }` (`200`
healthy, `503` otherwise). Register checks in `boot()`:

```php
Baseline::registerHealthCheck('database', fn () => DB::connection()->getPdo());
Baseline::registerHealthCheck('redshift', fn () => DB::connection('redshift')->select('SELECT 1'));
```

A check signals failure by throwing. Error detail is only included when
`app.debug` is on. Route/middleware are configurable.

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
`dashboards`, `health`. All env-overridable; the admin resolver and health checks
are registered in code (kept out of config so `config:cache` stays closure-free).

## Per-app adoption notes

| App | Admin resolver | Notes |
|-----|----------------|-------|
| compliance-api | `fn ($u) => $u->is_admin` | already the pilot; drop its `viewPulse`/`viewTelescope` gates |
| completions | `CSATF_ADMIN_EMAILS` | replaces the hard-coded `admin@csatf.org` gate and `viewApiDocs => true` |
| stp-docs | `fn ($u) => $u->hasAnyRole([...])` | already has its own SecurityHeaders/AppVersion — remove them in favour of this |
| team | `fn ($u) => $u->type === UserType::ADMIN` | drop its `viewTelescope` gate |

## Versioning

SemVer, tag-driven. Because this loads in production across every app, breaking
changes to config keys or the public API bump the major. Pin with `^1.0`.
