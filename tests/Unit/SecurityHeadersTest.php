<?php

declare(strict_types=1);

use Csatf\LaravelBaseline\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

function runSecurityHeaders(): Response
{
    return (new SecurityHeaders)->handle(Request::create('/'), fn () => new Response('ok'));
}

it('sets the baseline security headers', function () {
    $response = runSecurityHeaders();

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($response->headers->get('Permissions-Policy'))->toContain('geolocation=()');
});

it('emits HSTS only on an https app url', function () {
    config(['app.url' => 'http://example.test']);
    expect(runSecurityHeaders()->headers->has('Strict-Transport-Security'))->toBeFalse();

    config(['app.url' => 'https://example.test']);
    expect(runSecurityHeaders()->headers->get('Strict-Transport-Security'))->toContain('max-age=');
});

it('includes a one-year max-age and includeSubDomains by default', function () {
    config(['app.url' => 'https://example.test']);

    expect(runSecurityHeaders()->headers->get('Strict-Transport-Security'))
        ->toBe('max-age=31536000; includeSubDomains');
});

it('omits includeSubDomains when disabled', function () {
    config([
        'app.url' => 'https://example.test',
        'csatf-baseline.security_headers.hsts_include_subdomains' => false,
    ]);

    $value = runSecurityHeaders()->headers->get('Strict-Transport-Security');

    expect($value)->toBe('max-age=31536000')
        ->and($value)->not->toContain('includeSubDomains');
});

it('does nothing when disabled', function () {
    config(['csatf-baseline.security_headers.enabled' => false]);

    expect(runSecurityHeaders()->headers->has('X-Content-Type-Options'))->toBeFalse();
});

it('sets a CSP only when configured', function () {
    expect(runSecurityHeaders()->headers->has('Content-Security-Policy'))->toBeFalse();

    config(['csatf-baseline.security_headers.content_security_policy' => "default-src 'self'"]);
    expect(runSecurityHeaders()->headers->get('Content-Security-Policy'))->toBe("default-src 'self'");
});
