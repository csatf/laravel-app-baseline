<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $config = (array) config('csatf-baseline.security_headers', []);

        if (! ($config['enabled'] ?? true)) {
            return $response;
        }

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', (string) ($config['frame_options'] ?? 'SAMEORIGIN'));
        $headers->set('Referrer-Policy', (string) ($config['referrer_policy'] ?? 'strict-origin-when-cross-origin'));
        $headers->set('Permissions-Policy', (string) ($config['permissions_policy'] ?? 'camera=(), microphone=(), geolocation=()'));

        if (! empty($config['content_security_policy'])) {
            $headers->set('Content-Security-Policy', (string) $config['content_security_policy']);
        }

        if (($config['hsts'] ?? true) && str_starts_with((string) config('app.url'), 'https://')) {
            $value = 'max-age='.(int) ($config['hsts_max_age'] ?? 31536000);

            if ($config['hsts_include_subdomains'] ?? true) {
                $value .= '; includeSubDomains';
            }

            $headers->set('Strict-Transport-Security', $value);
        }

        return $response;
    }
}
