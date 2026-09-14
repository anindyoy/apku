<?php

use Symfony\Component\Yaml\Yaml;

test('workflow testing membagi seluruh suite tanpa melewatkan shard', function () {
    $workflow = Yaml::parseFile(__DIR__.'/../../.github/workflows/tests.yml');
    $job = $workflow['jobs']['test'];
    $steps = collect($job['steps'])->keyBy('name');
    $shards = $job['strategy']['matrix']['shard'];

    expect($shards)->toBe(range(1, count($shards)))
        ->and($job['strategy']['fail-fast'])->toBeFalse()
        ->and($steps['Run Tests in Parallel']['run'])
        ->toBe('vendor/bin/pest --parallel --processes=4 --shard=${{ matrix.shard }}/${{ strategy.job-total }}')
        ->and($job['env']['DB_DATABASE'])->toBe($job['services']['mysql']['env']['MYSQL_DATABASE'])
        ->and($job['env']['DB_PASSWORD'])->toBe($job['services']['mysql']['env']['MYSQL_ROOT_PASSWORD'])
        ->and($job['env']['APP_ENV'])->toBe('testing')
        ->and($job['env']['ADMIN_PASSWORD'])->not->toBeEmpty();
});

test('workflow testing memakai cache dependency dan membatalkan run usang', function () {
    $workflow = Yaml::parseFile(__DIR__.'/../../.github/workflows/tests.yml');
    $steps = collect($workflow['jobs']['test']['steps'])->keyBy('name');

    expect($workflow['concurrency']['group'])->toBe('${{ github.workflow }}-${{ github.ref }}')
        ->and($workflow['concurrency']['cancel-in-progress'])->toBeTrue()
        ->and($workflow['permissions'])->toBe(['contents' => 'read'])
        ->and($steps['Cache Composer Downloads']['with']['key'])->toContain("hashFiles('composer.lock')")
        ->and($steps['Cache Composer Downloads']['with']['path'])->toBe('${{ steps.composer-cache.outputs.dir }}')
        ->and($steps['Install Dependencies']['run'])->toContain('composer install')->not->toContain('--no-dev')
        ->and($steps['Setup Node.js']['with']['cache'])->toBe('npm')
        ->and($steps['Build Frontend Assets']['run'])->toContain('npm ci', 'npm run build');
});
