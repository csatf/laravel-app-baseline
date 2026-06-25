<?php

declare(strict_types=1);

return [

    /*
     * Security response headers, applied globally by the SecurityHeaders
     * middleware (so they also cover Filament panels). HSTS is only emitted on
     * an https app.url. CSP is intentionally null by default — roll it out
     * per-app, report-only first.
     */
    'security_headers' => [
        'enabled' => env('CSATF_SECURITY_HEADERS', true),
        'hsts' => env('CSATF_HSTS', true),
        'hsts_max_age' => (int) env('CSATF_HSTS_MAX_AGE', 86400),
        'frame_options' => env('CSATF_FRAME_OPTIONS', 'SAMEORIGIN'),
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'camera=(), microphone=(), geolocation=()',
        'content_security_policy' => env('CSATF_CSP'),
    ],

    /*
     * App version surfacing. `csatf:version:stamp` writes the resolved version
     * to this file at deploy time; AppVersion::current() reads it (falling back
     * to APP_VERSION, then .git/HEAD, then "dev").
     */
    'version' => [
        'stamp_path' => storage_path('app/version'),
    ],

    /*
     * Admin authorization for protected dashboards. Prefer registering a
     * resolver in code (Baseline::authorizeAdminUsing). The email allowlist is
     * a config-cache-safe fallback. With neither configured, access is denied.
     */
    'admin' => [
        'emails' => array_filter(array_map('trim', explode(',', (string) env('CSATF_ADMIN_EMAILS', '')))),
    ],

    /*
     * Which dashboard authorization gates this package defines. Each delegates
     * to the admin resolver above. Set a gate to false to leave it to the app.
     */
    'dashboards' => [
        'viewPulse' => true,
        'viewTelescope' => true,
        'viewApiDocs' => true,
        'viewHorizon' => false,
    ],

    /*
     * Health endpoint. Checks are registered in code via
     * Baseline::registerHealthCheck(); the route aggregates them to ok/error.
     */
    'health' => [
        'enabled' => true,
        'route' => env('CSATF_HEALTH_ROUTE', '/health'),
        'middleware' => ['throttle:60,1'],
    ],

];
