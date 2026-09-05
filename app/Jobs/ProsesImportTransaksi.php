<?php

namespace App\Jobs;

use App\Models\ImportTransaksi;
use App\Services\ImportTransaksiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProsesImportTransaksi implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public int $importTransaksiId) {}

    public function handle(ImportTransaksiService $service): void
    {
        $batch = ImportTransaksi::withoutGlobalScopes()->with('user')->findOrFail($this->importTransaksiId);

        if ($batch->status !== 'menunggu') {
            return;
        }

        $batch->update([
            'status' => 'diproses',
            'mulai_diproses_at' => now(),
            'pesan_error' => null,
        ]);
        $pathFile = $batch->path_file;

        try {
            $service->impor(
                $batch->user,
                Storage::disk('local')->path($batch->path_file),
                $batch->nama_file,
                $batch->pemetaan ?? [],
                $batch->buat_kategori_otomatis,
                $batch,
            );
        } finally {
            Storage::disk('local')->delete($pathFile);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $batch = ImportTransaksi::withoutGlobalScopes()->find($this->importTransaksiId);

        if ($batch === null) {
            return;
        }

        $batch->update([
            'status' => 'gagal',
            'pesan_error' => str($exception?->getMessage() ?? 'Import gagal diproses.')->limit(2000),
            'selesai_diproses_at' => now(),
        ]);

        Storage::disk('local')->delete($batch->path_file);
    }
}
