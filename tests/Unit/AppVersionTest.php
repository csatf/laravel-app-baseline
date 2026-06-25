<?php

declare(strict_types=1);

use Csatf\LaravelBaseline\Support\AppVersion;

it('formats an exact tag without the sha', function () {
    expect(AppVersion::format('v1.2.0', true, 'abc1234'))->toBe('1.2.0');
});

it('formats a non-exact tag with the short sha', function () {
    expect(AppVersion::format('v1.2.0', false, 'abc1234'))->toBe('1.2.0 (abc1234)');
});

it('returns null when there is no tag', function () {
    expect(AppVersion::format('', false, 'abc1234'))->toBeNull();
    expect(AppVersion::format(null, false, null))->toBeNull();
});

it('reads the version from the stamp file first', function () {
    $path = sys_get_temp_dir().'/csatf-version-'.uniqid();
    file_put_contents($path, "3.4.5\n");
    config(['csatf-baseline.version.stamp_path' => $path]);

    expect(AppVersion::current())->toBe('3.4.5');

    @unlink($path);
});

it('falls back to APP_VERSION when there is no stamp file', function () {
    config([
        'csatf-baseline.version.stamp_path' => sys_get_temp_dir().'/missing-'.uniqid(),
        'app.version' => '7.8.9',
    ]);

    expect(AppVersion::current())->toBe('7.8.9');
});
