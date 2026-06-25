<?php

declare(strict_types=1);

namespace Csatf\LaravelBaseline\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Opt-in standard JSON error envelope. This is never wired automatically — it
 * changes API response shapes, so each app turns it on explicitly from
 * bootstrap/app.php:
 *
 *   ->withExceptions(function (Exceptions $exceptions) {
 *       \Csatf\LaravelBaseline\Support\ApiExceptions::register($exceptions);
 *   })
 *
 * Only JSON requests are affected; web requests fall through to Laravel's
 * default rendering.
 */
class ApiExceptions
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(static function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            return self::toResponse($e);
        });
    }

    protected static function toResponse(Throwable $e): JsonResponse
    {
        [$status, $message, $errors] = self::map($e);

        $payload = ['message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($status >= 500 && config('app.debug')) {
            $payload['exception'] = $e::class;
        }

        return response()->json($payload, $status);
    }

    /**
     * @return array{0: int, 1: string, 2: array<string, mixed>|null}
     */
    protected static function map(Throwable $e): array
    {
        return match (true) {
            $e instanceof ValidationException => [422, 'The given data was invalid.', $e->errors()],
            $e instanceof ModelNotFoundException => [404, 'Resource not found.', null],
            $e instanceof AuthenticationException => [401, 'Unauthenticated.', null],
            $e instanceof AuthorizationException => [403, $e->getMessage() ?: 'This action is unauthorized.', null],
            $e instanceof HttpExceptionInterface => [$e->getStatusCode(), $e->getMessage() ?: 'HTTP error.', null],
            default => [500, config('app.debug') ? $e->getMessage() : 'Server error.', null],
        };
    }
}
