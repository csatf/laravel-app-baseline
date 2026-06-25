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
- `/health` endpoint aggregating checks registered via
  `Baseline::registerHealthCheck()`.
- Opt-in JSON exception envelope (`ApiExceptions::register()`).
- `csatf:baseline:install` command and publishable `config/csatf-baseline.php`.
