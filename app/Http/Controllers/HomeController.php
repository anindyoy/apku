<?php

namespace App\Http\Controllers;

use App\Models\PaketLangganan;
use App\Services\Langganan\HitungHematPaket;
use App\Services\PengaturanTrial;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(HitungHematPaket $hitungHematPaket, PengaturanTrial $pengaturanTrial): View
    {
        // Ambil paket aktif untuk ditampilkan di halaman depan agar harga selalu sesuai data admin.
        $paketAktif = PaketLangganan::query()
            ->where('is_active', true)
            ->orderByDesc('durasi_hari')
            ->get();

        // Hitung harga per bulan dan penanda hemat memakai layanan yang sama dengan halaman langganan.
        $paketHemat = $hitungHematPaket->handle($paketAktif);

        // Durasi dan status trial mengikuti setting admin agar tombol landing selalu sesuai program berjalan.
        return view('welcome', [
            'paketHemat' => $paketHemat,
            'trialDurasi' => $pengaturanTrial->durasiHari(),
            'trialAktif' => $pengaturanTrial->aktif(),
        ]);
    }
}
