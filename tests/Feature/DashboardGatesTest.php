<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;

function gateUser(string $email): object
{
    return new class($email)
    {
        public function __construct(public string $email) {}
    };
}

it('defines the enabled dashboard gates and not the disabled ones', function () {
    expect(Gate::has('viewPulse'))->toBeTrue()
        ->and(Gate::has('viewTelescope'))->toBeTrue()
        ->and(Gate::has('viewApiDocs'))->toBeTrue()
        ->and(Gate::has('viewHorizon'))->toBeFalse();
});

it('gates dashboards on the admin resolver', function () {
    config(['csatf-baseline.admin.emails' => ['admin@csatf.org']]);

    expect(Gate::forUser(gateUser('admin@csatf.org'))->allows('viewPulse'))->toBeTrue()
        ->and(Gate::forUser(gateUser('nope@csatf.org'))->allows('viewPulse'))->toBeFalse();
});
