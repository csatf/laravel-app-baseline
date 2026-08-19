# Changelog

All notable changes to this package are documented here. This project adheres to
[Semantic Versioning](https://semver.org/) and
[Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

## [1.2.0] - 2026-08-20

### Added
- Laravel 13 support: all seven `illuminate/*` constraints widened to
  `^11.0 || ^12.0 || ^13.0`, and `orchestra/testbench` to
  `^9.0 || ^10.0 || ^11.0`.
- Verified against Laravel 13.26.1 / Testbench 11.2.0 / PHP 8.4: all 24 tests
  pass and PHPStan reports no errors. No source changes were required.

### Fixed
- Dashboard gates (`viewPulse`, `viewTelescope`, `viewApiDocs`) are now
  registered in an `app booted()` callback instead of directly in `boot()`.
  Packages such as Laravel Pulse define their own `viewPulse` default in their
  `boot()`, and — booting after this package — would overwrite the baseline's
  admin gate, locking admins out (the gate fell back to Pulse's
  `environment('local')` default, i.e. denied in staging/production). Deferring
  to `booted()` makes the baseline authoritative regardless of provider order.

## [1.1.0] - 2026-07-16

### Changed
- HSTS is now emitted as `max-age=31536000; includeSubDomains` by default
  (previously `max-age=86400`, no sub-domain directive). This strengthens the
  header to a one-year policy covering sub-domains, matching common pen-test
  remediation guidance. Consuming apps pick this up on their next
  `composer update`; override via `CSATF_HSTS_MAX_AGE` /
  `CSATF_HSTS_INCLUDE_SUBDOMAINS=false`.

### Added
- `hsts_include_subdomains` config key (`CSATF_HSTS_INCLUDE_SUBDOMAINS`, default
  `true`) to control the `includeSubDomains` HSTS directive.

## [1.0.0] - 2026-06-25

### Added
- `SecurityHeaders` middleware (auto-appended globally, config-gated).
- `AppVersion` resolver + `csatf:version:stamp` command + `@appVersion` Blade
  directive (git-binary-free version surfacing).
- Config-driven dashboard admin gates (`viewPulse`, `viewTelescope`,
  `viewApiDocs`, `viewHorizon`) delegating to a single `Baseline::isAdmin`
  resolver; fail-closed when unconfigured.
- Database-connectivity health wired into Laravel's built-in `/up` via a
  `DiagnosingHealth` listener (config: `health.connections`, e.g.
  `CSATF_HEALTH_CONNECTIONS=pgsql,redshift`). `/up` returns non-200 when a
  configured connection is unreachable.
- Opt-in JSON exception envelope (`ApiExceptions::register()`).
- `csatf:baseline:install` command and publishable `config/csatf-baseline.php`.
- Test suite (Pest + Orchestra Testbench).

[Unreleased]: https://github.com/csatf/laravel-app-baseline/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/csatf/laravel-app-baseline/releases/tag/v1.0.0
