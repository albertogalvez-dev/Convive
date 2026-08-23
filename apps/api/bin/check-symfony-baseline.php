<?php

declare(strict_types=1);

/**
 * Prevent a partial Symfony component upgrade from bypassing Flex's preferred
 * framework line through a direct Composer constraint.
 */

$projectDirectory = dirname(__DIR__);
$composer = readJson($projectDirectory.'/composer.json');
$lock = readJson($projectDirectory.'/composer.lock');

$baseline = $composer['extra']['symfony']['require'] ?? null;
if (!is_string($baseline) || '' === $baseline) {
    fail('composer.json must declare extra.symfony.require.');
}

$independentPackages = [
    'symfony/flex' => 'Composer plugin with an independent release line',
    'symfony/maker-bundle' => 'development bundle with an independent release line',
    'symfony/monolog-bundle' => 'integration bundle with an independent release line',
];

$requirements = array_merge(
    is_array($composer['require'] ?? null) ? $composer['require'] : [],
    is_array($composer['require-dev'] ?? null) ? $composer['require-dev'] : [],
);

$errors = [];
foreach ($requirements as $package => $constraint) {
    if (!is_string($package) || !str_starts_with($package, 'symfony/')) {
        continue;
    }
    if (isset($independentPackages[$package])) {
        continue;
    }
    if ($constraint !== $baseline) {
        $errors[] = sprintf(
            '%s requires %s; every Symfony framework component must require %s.',
            $package,
            is_string($constraint) ? $constraint : get_debug_type($constraint),
            $baseline,
        );
    }
}

$lockedPackages = array_merge(
    is_array($lock['packages'] ?? null) ? $lock['packages'] : [],
    is_array($lock['packages-dev'] ?? null) ? $lock['packages-dev'] : [],
);
$lockedPrefix = 'v'.rtrim($baseline, '*');

foreach ($lockedPackages as $package) {
    if (!is_array($package)) {
        continue;
    }

    $name = $package['name'] ?? null;
    $version = $package['version'] ?? null;
    if (!is_string($name) || !str_starts_with($name, 'symfony/')) {
        continue;
    }
    if (isset($independentPackages[$name])
        || str_starts_with($name, 'symfony/polyfill-')
        || str_ends_with($name, '-contracts')) {
        continue;
    }
    if (!is_string($version) || !str_starts_with($version, $lockedPrefix)) {
        $errors[] = sprintf(
            '%s is locked at %s; expected the %s component line.',
            $name,
            is_string($version) ? $version : get_debug_type($version),
            $baseline,
        );
    }
}

if ([] !== $errors) {
    fail(implode(PHP_EOL, $errors));
}

printf(
    "Symfony requirements and locked components consistently use %s.\n",
    $baseline,
);

/** @return array<string, mixed> */
function readJson(string $path): array
{
    $contents = file_get_contents($path);
    if (false === $contents) {
        fail(sprintf('Cannot read %s.', $path));
    }

    $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        fail(sprintf('%s must contain a JSON object.', $path));
    }

    return $decoded;
}

function fail(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}
