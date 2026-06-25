<?php

declare(strict_types=1);

use Csatf\LaravelBaseline\Support\ApiExceptions;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function jsonRequest(): Request
{
    return Request::create('/api/x', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
}

it('ignores non-json requests', function () {
    expect(ApiExceptions::handle(new RuntimeException('x'), Request::create('/web')))->toBeNull();
});

it('maps validation exceptions to 422 with errors', function () {
    $response = ApiExceptions::handle(
        ValidationException::withMessages(['email' => ['Required.']]),
        jsonRequest(),
    );

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toHaveKey('errors.email');
});

it('maps not-found to 404', function () {
    expect(ApiExceptions::handle(new NotFoundHttpException, jsonRequest())->getStatusCode())->toBe(404);
});

it('maps authentication exceptions to 401', function () {
    expect(ApiExceptions::handle(new AuthenticationException, jsonRequest())->getStatusCode())->toBe(401);
});

it('hides internal messages when debug is off', function () {
    config(['app.debug' => false]);

    $response = ApiExceptions::handle(new RuntimeException('secret detail'), jsonRequest());

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getData(true)['message'])->toBe('Server error.');
});
