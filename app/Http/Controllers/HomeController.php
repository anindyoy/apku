<?php

namespace App\Http\Controllers;

use App\Models\PaketLangganan;
use App\Services\Langganan\HitungHematPaket;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(HitungHematPaket $hitungHematPaket): View
    {
        // Ambil paket aktif untuk ditampilkan di halaman depan agar harga selalu sesuai data admin.
        $paketAktif = PaketLangganan::query()
            ->where('is_active', true)
            ->orderByDesc('durasi_hari')
            ->get();

        // Hitung harga per bulan dan penanda hemat memakai layanan yang sama dengan halaman langganan.
        $paketHemat = $hitungHematPaket->handle($paketAktif);

        return view('welcome', ['paketHemat' => $paketHemat]);
    }
}
