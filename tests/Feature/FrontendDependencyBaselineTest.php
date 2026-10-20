<?php

test('frontend lock retains patched dependency baselines', function (string $package, string $minimum) {
    $lock = json_decode(file_get_contents(base_path('package-lock.json')), true, flags: JSON_THROW_ON_ERROR);
    $matches = collect($lock['packages'])->filter(
        fn ($entry, $path) => $path === "node_modules/{$package}" || str_ends_with($path, "/node_modules/{$package}")
    );
    expect($matches)->not->toBeEmpty();
    foreach ($matches as $entry) {
        expect(version_compare($entry['version'], $minimum, '>='), "{$package} must be >= {$minimum}")->toBeTrue();
    }
})->with([
    ['axios', '1.20.0'],
    ['vite', '8.3.4'],
    ['postcss', '8.5.31'],
    ['shell-quote', '1.12.0'],
    ['postcss-selector-parser', '7.1.6'],
    ['form-data', '4.0.6'],
    ['source-map-js', '1.2.2'],
    ['nanoid', '3.3.18'],
]);

test('security updates preserve the existing Tailwind major and smoke test entrypoint', function () {
    $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
    $lock = json_decode(file_get_contents(base_path('package-lock.json')), true, flags: JSON_THROW_ON_ERROR);
    expect($lock['packages']['node_modules/tailwindcss']['version'])->toStartWith('3.')
        ->and($package['scripts']['test:frontend'])->toBe('node --test tests/Frontend/*.test.mjs');
});
