<?php

declare(strict_types=1);

// Jalankan dengan binary PHP yang sama dengan cron dan aplikasi web.
$root = dirname(__DIR__);
$dryRun = in_array('--dry-run', $argv, true);
$php = PHP_BINARY;

if (PHP_VERSION_ID < 80400) {
    fwrite(STDERR, "PHP CLI minimal 8.4 diperlukan.\n");
    exit(1);
}

if (! is_file($root.'/.env') || ! is_file($root.'/vendor/autoload.php') || ! is_file($root.'/public/build/manifest.json')) {
    fwrite(STDERR, "Siapkan .env, dependensi Composer, dan aset Vite sebelum optimasi.\n");
    exit(1);
}

$environment = file_get_contents($root.'/.env');
if (! preg_match('/^APP_ENV=production\s*$/m', $environment) || ! preg_match('/^APP_DEBUG=false\s*$/m', $environment)) {
    fwrite(STDERR, "APP_ENV=production dan APP_DEBUG=false diperlukan.\n");
    exit(1);
}

$commands = [
    'filament:optimize',
    'optimize',
];

foreach ($commands as $command) {
    $args = [$php, $root.'/artisan', $command];
    fwrite(STDOUT, implode(' ', array_map('escapeshellarg', $args)).PHP_EOL);
    if ($dryRun) {
        continue;
    }

    $process = proc_open($args, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root);
    if (! is_resource($process) || proc_close($process) !== 0) {
        fwrite(STDERR, "Optimasi gagal pada {$command}; hentikan rilis dan periksa log.\n");
        exit(1);
    }
}
