<?php

namespace Database\Factories;

use App\Enums\StatusLangganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LanggananFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_order' => 'LGN-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'user_id' => User::factory(),
            'paket_langganan_id' => PaketLangganan::factory(),
            'metode_pembayaran_id' => MetodePembayaran::factory(),
            'label_paket' => 'Premium 30 Hari',
            'harga' => 25000,
            'durasi_hari' => 30,
            'label_metode_pembayaran' => 'Transfer Bank',
            'detail_pembayaran' => [
                'jenis' => 'bank',
                'nama_penyedia' => 'Bank Contoh',
                'nomor_tujuan' => '1234567890',
                'nama_pemilik' => 'APKu',
                'instruksi' => 'Transfer sesuai total order.',
            ],
            'persentase_diskon' => 0,
            'nominal_diskon' => 0,
            'total_pembayaran' => 25000,
            'status' => StatusLangganan::MenungguPembayaran,
        ];
    }
}
