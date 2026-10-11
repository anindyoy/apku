<?php

namespace App\Services\Langganan;

use App\Models\PaketLangganan;
use Illuminate\Support\Collection;

class HitungHematPaket
{
    /**
     * Menghitung hemat per paket dari koleksi paket aktif.
     *
     * - Harga acuan per bulan diambil otomatis dari paket aktif berdurasi terpendek.
     * - Jumlah bulan dihitung dari pembulatan hari / 30 (365 hari menjadi 12 bulan).
     * - Satu paket dengan hemat terbesar disorot otomatis (is_terbaik).
     * - Urutan hasil dari durasi terpanjang ke terpendek.
     *
     * @param  Collection<int, PaketLangganan>  $paket  Paket aktif.
     * @return Collection<int, array{id: int, label: string, harga: int, hari: int, bulan: int, per_bulan: float, harga_coret: float, hemat_bulan: float, hemat_total: float, persen: int, is_acuan: bool, is_terbaik: bool}>
     */
    public function handle(Collection $paket): Collection
    {
        $items = $paket->map(fn ($p) => [
            'id' => $p->id,
            'label' => $p->label,
            'harga' => (int) $p->harga,
            'hari' => (int) $p->durasi_hari,
            'bulan' => max(1, (int) round(((int) $p->durasi_hari) / 30)),
        ]);

        $acuan = $items->sortBy('hari')->first();

        if (! $acuan) {
            return collect();
        }

        $acuanPerBulan = (float) ($acuan['harga'] / $acuan['bulan']);

        $hasil = $items->map(function ($i) use ($acuanPerBulan, $acuan) {
            $perBulan = (float) ($i['harga'] / $i['bulan']);
            $coret = (float) ($acuanPerBulan * $i['bulan']);

            return $i + [
                'per_bulan' => $perBulan,
                'harga_coret' => $coret,
                'hemat_bulan' => (float) max(0, $acuanPerBulan - $perBulan),
                'hemat_total' => (float) max(0, $coret - $i['harga']),
                'persen' => $coret > 0 ? (int) floor((1 - $i['harga'] / $coret) * 100) : 0,
                'is_acuan' => $i['id'] === $acuan['id'],
            ];
        });

        $terbaik = $hasil->sortByDesc('hemat_bulan')->first();

        return $hasil
            ->map(fn ($i) => $i + [
                'is_terbaik' => $terbaik && $terbaik['hemat_bulan'] > 0 && $i['id'] === $terbaik['id'],
            ])
            ->sortByDesc('hari')
            ->values();
    }
}
