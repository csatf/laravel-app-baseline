<?php

declare(strict_types=1);

use Csatf\LaravelBaseline\Baseline;

function fakeUser(string $email): object
{
    return new class($email)
    {
        public function __construct(public string $email) {}
    };
}

it('denies everyone when nothing is configured (fail closed)', function () {
    config(['csatf-baseline.admin.emails' => []]);
    $baseline = new Baseline;

    expect($baseline->isAdmin(fakeUser('a@csatf.org')))->toBeFalse()
        ->and($baseline->isAdmin(null))->toBeFalse();
});

it('authorizes via the email allowlist', function () {
    config(['csatf-baseline.admin.emails' => ['admin@csatf.org']]);
    $baseline = new Baseline;

    expect($baseline->isAdmin(fakeUser('admin@csatf.org')))->toBeTrue()
        ->and($baseline->isAdmin(fakeUser('nope@csatf.org')))->toBeFalse();
});

it('prefers a registered resolver over the email allowlist', function () {
    config(['csatf-baseline.admin.emails' => ['admin@csatf.org']]);
    $baseline = new Baseline;
    $baseline->authorizeAdminUsing(fn ($user) => $user->email === 'override@csatf.org');

    expect($baseline->isAdmin(fakeUser('override@csatf.org')))->toBeTrue()
        ->and($baseline->isAdmin(fakeUser('admin@csatf.org')))->toBeFalse();
});
