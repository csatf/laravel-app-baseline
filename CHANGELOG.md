# Changelog

All notable changes to this package are documented here. This project adheres to
[Semantic Versioning](https://semver.org/) and
[Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

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
