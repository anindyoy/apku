<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\HargaEmas;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HargaEmasService
{
    /** @return array{harga_per_gram:int, berlaku_pada:Carbon, provider:string, sumber:string, status:string} */
    public function hargaBuyback(BukuKas $bukuKas, bool $segarkan = false): array
    {
        try {
            $data = $segarkan
                ? $this->ambilDariApi()
                : Cache::remember('harga-emas:buyback', now()->addHours((int) config('services.harga_emas.cache_hours', 3)), fn (): array => $this->ambilDariApi());

            $snapshot = HargaEmas::firstOrCreate([
                'provider' => $data['provider'],
                'sumber' => 'api',
                'jenis_harga' => 'buyback',
                'harga_per_gram' => $data['harga_per_gram'],
                'berlaku_pada' => $data['berlaku_pada'],
            ], [
                'diambil_pada' => now(),
                'metadata' => $data['metadata'],
            ]);

            return $this->format($snapshot, 'terbaru');
        } catch (\Throwable $exception) {
            report($exception);

            $snapshot = HargaEmas::query()
                ->where('jenis_harga', 'buyback')
                ->where(function ($query) use ($bukuKas): void {
                    $query->where(fn ($query) => $query->where('sumber', 'manual')->where('buku_kas_id', $bukuKas->id))
                        ->orWhere('sumber', 'api');
                })
                ->orderByRaw('CASE WHEN sumber = ? AND buku_kas_id = ? THEN 0 ELSE 1 END', ['manual', $bukuKas->id])
                ->orderByDesc('berlaku_pada')
                ->orderByDesc('id')
                ->first();

            if (! $snapshot) {
                throw new RuntimeException('Harga emas belum tersedia. Masukkan harga buyback manual.', previous: $exception);
            }

            return $this->format($snapshot, $snapshot->sumber === 'manual' ? 'manual' : 'cache');
        }
    }

    /** @return array{harga_per_gram:int, berlaku_pada:Carbon, provider:string, sumber:string, status:string} */
    public function simpanManual(BukuKas $bukuKas, User $user, int $hargaPerGram, mixed $berlakuPada = null): array
    {
        if (! $user->dapatMengelolaTransaksiPada($bukuKas) || $hargaPerGram <= 0) {
            throw new RuntimeException('Harga manual tidak valid.');
        }

        $snapshot = HargaEmas::create([
            'buku_kas_id' => $bukuKas->id,
            'user_id' => $user->id,
            'provider' => 'Manual',
            'sumber' => 'manual',
            'jenis_harga' => 'buyback',
            'harga_per_gram' => $hargaPerGram,
            'berlaku_pada' => $berlakuPada ?? now(),
            'diambil_pada' => now(),
        ]);

        return $this->format($snapshot, 'manual');
    }

    /** @return array{harga_per_gram:int, berlaku_pada:Carbon, provider:string, metadata:array<string,mixed>} */
    private function ambilDariApi(): array
    {
        $response = Http::acceptJson()
            ->timeout((int) config('services.harga_emas.timeout', 8))
            ->retry(2, 250)
            ->get((string) config('services.harga_emas.url'))
            ->throw()
            ->json();

        $baris = collect($response['data'] ?? [])
            ->filter(fn ($item): bool => is_array($item)
                && ($item['material'] ?? null) === 'gold'
                && ($item['currency'] ?? null) === 'IDR'
                && (float) ($item['weight'] ?? 0) > 0
                && (int) ($item['buybackPrice'] ?? 0) > 0)
            ->sortBy(fn ($item): float => abs((float) $item['weight'] - 1))
            ->first();

        if (! $baris) {
            throw new RuntimeException('Respons penyedia tidak memuat harga buyback emas yang valid.');
        }

        return [
            'harga_per_gram' => (int) round((int) $baris['buybackPrice'] / (float) $baris['weight']),
            'berlaku_pada' => Carbon::parse($baris['recordedDate'] ?? $response['timestamp'] ?? now()),
            'provider' => (string) ($baris['displayName'] ?? $baris['source'] ?? 'Logam Mulia API'),
            'metadata' => ['source' => $baris['source'] ?? null, 'material_type' => $baris['materialType'] ?? null],
        ];
    }

    /** @return array{harga_per_gram:int, berlaku_pada:Carbon, provider:string, sumber:string, status:string} */
    private function format(HargaEmas $snapshot, string $status): array
    {
        return [
            'harga_per_gram' => (int) $snapshot->harga_per_gram,
            'berlaku_pada' => $snapshot->berlaku_pada,
            'provider' => $snapshot->provider,
            'sumber' => $snapshot->sumber,
            'status' => $status,
        ];
    }
}
