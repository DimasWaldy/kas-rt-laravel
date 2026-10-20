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
    ['form-data', '4.0.6'],
    ['source-map-js', '1.2.2'],
    ['nanoid', '3.3.18'],
]);

test('Tailwind four replaces the vulnerable legacy compiler dependency chain', function () {
    $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
    $lock = json_decode(file_get_contents(base_path('package-lock.json')), true, flags: JSON_THROW_ON_ERROR);
    expect($lock['packages']['node_modules/tailwindcss']['version'])->toStartWith('4.')
        ->and($lock['packages']['node_modules/@tailwindcss/vite']['version'])->toStartWith('4.')
        ->and($package['scripts']['test:frontend'])->toBe('node --test tests/Frontend/*.test.mjs');
    foreach (['braces', 'micromatch', 'fast-glob', 'postcss-selector-parser', 'postcss-nested', 'autoprefixer'] as $legacy) {
        expect(collect(array_keys($lock['packages']))->contains(
            fn ($path) => $path === "node_modules/{$legacy}" || str_ends_with($path, "/node_modules/{$legacy}")
        ), "{$legacy} must not return to the build tree")->toBeFalse();
    }
});

test('Tailwind four sources and dark mode remain explicit and obsolete classes are removed', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    expect($css)->toContain("@import 'tailwindcss' source(none)")
        ->toContain("@source '../views'")
        ->toContain("@source '../js'")
        ->toContain('@custom-variant dark (&:where(.dark, .dark *))')
        ->toContain("@plugin '@tailwindcss/forms'")
        ->not->toContain('@tailwind ');
    foreach (\Illuminate\Support\Facades\File::allFiles(resource_path('views')) as $file) {
        expect($file->getContents(), $file->getRelativePathname())
            ->not->toMatch('/\b(?:bg-opacity-\d+|ring-opacity-\d+|flex-shrink-\d+|flex-grow-\d+|bg-gradient-to-\w+|outline-none)\b/');
    }
});
