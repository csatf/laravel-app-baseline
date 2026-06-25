# Changelog

All notable changes to this package are documented here. This project adheres to
[Semantic Versioning](https://semver.org/) and
[Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

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
