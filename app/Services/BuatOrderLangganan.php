<?php

namespace App\Services;

use App\Enums\StatusLangganan;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BuatOrderLangganan
{
    public function handle(User $user, PaketLangganan $paket, MetodePembayaran $metode): Langganan
    {
        if (! $paket->is_active) {
            throw ValidationException::withMessages(['paket_langganan_id' => 'Paket langganan tidak aktif.']);
        }

        if (! $metode->is_active) {
            throw ValidationException::withMessages(['metode_pembayaran_id' => 'Metode pembayaran tidak aktif.']);
        }

        return Langganan::create([
            'kode_order' => $this->buatKodeOrder(),
            'user_id' => $user->id,
            'paket_langganan_id' => $paket->id,
            'metode_pembayaran_id' => $metode->id,
            'label_paket' => $paket->label,
            'harga' => $paket->harga,
            'durasi_hari' => $paket->durasi_hari,
            'label_metode_pembayaran' => $metode->label,
            'detail_pembayaran' => [
                'jenis' => $metode->jenis,
                'nama_penyedia' => $metode->nama_penyedia,
                'nomor_tujuan' => $metode->nomor_tujuan,
                'nama_pemilik' => $metode->nama_pemilik,
                'instruksi' => $metode->instruksi,
                'gambar_qr_path' => $metode->gambar_qr_path,
            ],
            'status' => StatusLangganan::MenungguPembayaran,
        ]);
    }

    private function buatKodeOrder(): string
    {
        do {
            $kode = 'LGN-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Langganan::where('kode_order', $kode)->exists());

        return $kode;
    }
}
