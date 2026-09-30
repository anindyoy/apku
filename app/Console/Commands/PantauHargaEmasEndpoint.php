<?php

namespace App\Console\Commands;

use App\Services\PengaturanHargaEmas;
use App\Services\TelegramErrorNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

class PantauHargaEmasEndpoint extends Command
{
    protected $signature = 'harga-emas:pantau';

    protected $description = 'Memeriksa kestabilan endpoint harga buyback emas';

    public function handle(): int
    {
        $pengaturan = app(PengaturanHargaEmas::class)->semua();
        $mulai = microtime(true);

        try {
            $response = Http::acceptJson()
                ->withUserAgent('APKu-Endpoint-Monitor/1.0')
                ->timeout((int) $pengaturan['timeout'])
                ->get((string) $pengaturan['url'])
                ->throw();

            if (! str_contains(strtolower($response->header('Content-Type', '')), 'application/json')) {
                throw new RuntimeException('Endpoint tidak mengembalikan konten JSON.');
            }

            $data = $response->json();
            if (! is_array($data) || ($data['success'] ?? false) !== true) {
                throw new RuntimeException('Struktur respons endpoint harga emas tidak valid.');
            }

            $hargaValid = collect($data['data'] ?? [])->filter(fn ($item): bool => is_array($item)
                && ($item['material'] ?? null) === 'gold'
                && ($item['currency'] ?? null) === 'IDR'
                && (float) ($item['weight'] ?? 0) > 0
                && (int) ($item['buybackPrice'] ?? 0) > 0);

            if ($hargaValid->isEmpty()) {
                throw new RuntimeException('Respons tidak memiliki harga buyback emas IDR yang valid.');
            }

            $tanggalTerbaru = $hargaValid
                ->pluck('recordedDate')
                ->filter(fn ($tanggal): bool => is_string($tanggal) && $tanggal !== '')
                ->map(fn (string $tanggal): int => Carbon::parse($tanggal)->getTimestamp())
                ->max();

            if (! $tanggalTerbaru || Carbon::createFromTimestamp($tanggalTerbaru)->lt(now()->subDays(7))) {
                throw new RuntimeException('Harga buyback terakhir berusia lebih dari tujuh hari.');
            }

            $durasi = microtime(true) - $mulai;
            if ($durasi >= 15) {
                throw new RuntimeException('Respons endpoint melebihi 15 detik.');
            }

            $this->info(sprintf('Endpoint harga emas stabil (HTTP %d, %.2f detik).', $response->status(), $durasi));

            return SymfonyCommand::SUCCESS;
        } catch (Throwable $exception) {
            app(TelegramErrorNotifier::class)->send($exception);
            $this->error($exception->getMessage());

            return SymfonyCommand::FAILURE;
        }
    }
}