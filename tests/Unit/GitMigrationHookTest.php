<?php

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

it('runs migration and composer hooks after pulling relevant changes only', function (string $mode, string $change, int $exitCode, int $composerExitCode = 0) {
    $filesystem = new Filesystem;
    $directory = sys_get_temp_dir().'/apku-hooks-'.bin2hex(random_bytes(8));
    $upstream = $directory.'/upstream';
    $checkout = $directory.'/checkout';
    $filesystem->makeDirectory($upstream.'/database/migrations', 0755, true);
    $filesystem->makeDirectory($directory.'/bin');
    file_put_contents($directory.'/bin/composer2', "#!/bin/sh\nprintf '%s\\n' \"\$*\" >> composer-calls.txt\nprintf 'composer\\n' >> events.txt\nexit $composerExitCode\n");
    chmod($directory.'/bin/composer2', 0755);
    $run = function (array $command, string $cwd) use ($directory) {
        $process = new Process($command, $cwd, ['PATH' => $directory.'/bin'.PATH_SEPARATOR.getenv('PATH')]);
        $process->mustRun();

        return trim($process->getOutput().$process->getErrorOutput());
    };
    $git = fn (string $cwd, array $args) => $run(array_merge([
        'git', '-c', 'user.name=Hook Test', '-c', 'user.email=hook@example.test',
        '-c', 'commit.gpgsign=false',
    ], $args), $cwd);

    try {
        $git($upstream, ['init', '-b', 'main']);
        file_put_contents($upstream.'/database/migrations/existing.php', '<?php');
        file_put_contents($upstream.'/composer.json', '{}');
        file_put_contents($upstream.'/composer.lock', '{}');
        file_put_contents($upstream.'/artisan', '<?php file_put_contents(__DIR__."/events.txt", "migrate\n", FILE_APPEND); file_put_contents(__DIR__."/calls.json", json_encode($argv)); exit('.$exitCode.');');
        $git($upstream, ['add', '.']);
        $git($upstream, ['commit', '-m', 'Initial']);
        $git($directory, ['clone', $upstream, $checkout]);
        $filesystem->copyDirectory(dirname(__DIR__, 2).'/.githooks', $checkout.'/.githooks');
        chmod($checkout.'/.githooks/post-merge', 0755);
        chmod($checkout.'/.githooks/post-rewrite', 0755);
        $git($checkout, ['config', 'core.hooksPath', '.githooks']);

        if ($mode !== 'ff') {
            file_put_contents($checkout.'/local.txt', 'local');
            $git($checkout, ['add', 'local.txt']);
            $git($checkout, ['commit', '-m', 'Local change']);
        }

        if (in_array($change, ['added', 'combined'], true)) {
            file_put_contents($upstream.'/database/migrations/new.php', '<?php');
            file_put_contents($upstream.'/database/migrations/another.php', '<?php');
        } elseif ($change === 'modified') {
            file_put_contents($upstream.'/database/migrations/existing.php', '<?php // Perubahan');
        } elseif ($change === 'deleted') {
            unlink($upstream.'/database/migrations/existing.php');
        } else {
            file_put_contents($upstream.'/other.txt', 'other');
        }

        $composerChanged = in_array($change, ['composer-json', 'composer-lock', 'combined'], true);
        if (in_array($change, ['composer-json', 'combined'], true)) {
            file_put_contents($upstream.'/composer.json', '{"require":{}}');
        }
        if (in_array($change, ['composer-lock', 'combined'], true)) {
            file_put_contents($upstream.'/composer.lock', '{"packages":[]}');
        }

        $git($upstream, ['add', '.']);
        $git($upstream, ['commit', '-m', 'Remote change']);
        $output = $git($checkout, ['pull', $mode === 'rebase' ? '--rebase' : '--no-rebase']);
        $shouldMigrate = in_array($change, ['added', 'combined'], true) && $composerExitCode === 0;
        expect(file_exists($checkout.'/calls.json'))->toBe($shouldMigrate);
        expect(file_exists($checkout.'/composer-calls.txt'))->toBe($composerChanged);
        if ($composerChanged) {
            expect(file_get_contents($checkout.'/composer-calls.txt'))->toBe("update --no-interaction\n");
            expect(file_get_contents($checkout.'/events.txt'))->toBe($shouldMigrate ? "composer\nmigrate\n" : "composer\n");
            expect($output)->toContain($composerExitCode === 0 ? 'Pembaruan Composer selesai' : 'Composer gagal');
        }

        if ($shouldMigrate) {
            expect(json_decode(file_get_contents($checkout.'/calls.json'), true))
                ->toBe(['artisan', 'migrate', '--force', '--no-interaction']);
            expect($output)->toContain('Migration baru ditemukan');
            if ($exitCode === 0) {
                expect($output)->toContain('Migrasi selesai');
            } else {
                expect($output)->toContain('Migrasi gagal. Perubahan Git sudah diterapkan');
            }
            expect(substr_count($output, 'Migration baru ditemukan'))->toBe(1);
            unlink($checkout.'/calls.json');
        }
        if ($composerChanged) {
            unlink($checkout.'/composer-calls.txt');
        }
        $git($checkout, ['pull', '--no-rebase']);
        expect(file_exists($checkout.'/calls.json'))->toBeFalse();
        expect(file_exists($checkout.'/composer-calls.txt'))->toBeFalse();
    } finally {
        $filesystem->deleteDirectory($directory);
    }
})->with([
    'fast-forward' => ['ff', 'added', 0],
    'merge' => ['merge', 'added', 0],
    'rebase' => ['rebase', 'added', 0],
    'migration diubah' => ['ff', 'modified', 0],
    'migration dihapus' => ['ff', 'deleted', 0],
    'file lain' => ['ff', 'other', 0],
    'artisan gagal' => ['ff', 'added', 1],
    'composer json berubah' => ['ff', 'composer-json', 0],
    'composer lock berubah' => ['ff', 'composer-lock', 0],
    'composer sebelum migrasi' => ['ff', 'combined', 0],
    'composer pada merge' => ['merge', 'combined', 0],
    'composer pada rebase' => ['rebase', 'combined', 0],
    'composer gagal menghentikan migrasi' => ['ff', 'combined', 0, 1],
]);
