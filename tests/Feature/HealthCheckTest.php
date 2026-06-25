<?php

declare(strict_types=1);

use Illuminate\Foundation\Events\DiagnosingHealth;

it('passes /up when the configured connection is reachable', function () {
    config(['csatf-baseline.health.connections' => []]); // -> default (sqlite :memory:)

    event(new DiagnosingHealth);
})->throwsNoExceptions();

it('fails /up when a configured connection cannot be reached', function () {
    config([
        'csatf-baseline.health.connections' => ['broken'],
        'database.connections.broken' => [
            'driver' => 'sqlite',
            'database' => '/nonexistent/'.uniqid().'.sqlite',
        ],
    ]);

    // The DiagnosingHealth listener pings the broken connection and throws,
    // which is what makes Laravel's /up endpoint return a non-200.
    $threw = false;
    try {
        event(new DiagnosingHealth);
    } catch (Throwable) {
        $threw = true;
    }

    expect($threw)->toBeTrue();
});
