<?php

test('skrip optimasi rilis menolak prasyarat yang belum siap', function () {
    $directory = sys_get_temp_dir().'/apku-release-'.bin2hex(random_bytes(6));
    mkdir($directory.'/scripts', 0777, true);
    copy(__DIR__.'/../../scripts/release-optimize.php', $directory.'/scripts/release-optimize.php');

    try {
        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg($directory.'/scripts/release-optimize.php').' --dry-run 2>&1';
        exec($command, $output, $code);

        expect($code)->toBe(1)
            ->and(implode("\n", $output))->toContain('Siapkan .env, dependensi Composer, dan aset Vite');

        mkdir($directory.'/vendor');
        mkdir($directory.'/public/build', 0777, true);
        file_put_contents($directory.'/vendor/autoload.php', '<?php');
        file_put_contents($directory.'/public/build/manifest.json', '{}');
        file_put_contents($directory.'/.env', "APP_ENV=production\nAPP_DEBUG=false\n");
        $output = [];
        exec($command, $output, $code);

        expect($code)->toBe(0)
            ->and($output)->toHaveCount(2)
            ->and($output[0])->toContain('filament:optimize')
            ->and($output[1])->toContain('optimize');
    } finally {
        unlink($directory.'/.env');
        unlink($directory.'/vendor/autoload.php');
        unlink($directory.'/public/build/manifest.json');
        unlink($directory.'/scripts/release-optimize.php');
        rmdir($directory.'/public/build');
        rmdir($directory.'/public');
        rmdir($directory.'/vendor');
        rmdir($directory.'/scripts');
        rmdir($directory);
    }
});
