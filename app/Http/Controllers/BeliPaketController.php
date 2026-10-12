<?php

namespace App\Http\Controllers;

use App\Filament\Pages\Onboarding;
use App\Filament\Resources\LanggananResource;
use App\Models\PaketLangganan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BeliPaketController extends Controller
{
    // Kunci session untuk pilihan paket tamu sebelum akun dibuat.
    public const SESSION_KEY = 'paket_dipilih_id';

    public function __invoke(Request $request, int $paket): RedirectResponse
    {
        // Harga tidak pernah dipercaya dari input; hanya ID yang dipakai lalu paket dimuat ulang dari database.
        $paketAktif = PaketLangganan::query()->where('is_active', true)->find($paket);

        if (! $paketAktif) {
            // Paket tidak valid kembali ke bagian harga dengan pesan singkat.
            return redirect()->to(route('home').'#harga')->with('paket_gagal', 'Paket tidak tersedia. Pilih paket lain yang masih aktif.');
        }

        $pengguna = $request->user();

        // Admin tidak membuat order; arahkan ke dasbor admin.
        if ($pengguna && $pengguna->isAdmin()) {
            return redirect()->to(filament()->getUrl());
        }

        if ($pengguna) {
            // Pengguna login yang belum onboarding: simpan ke kolom agar terbawa sampai onboarding selesai.
            if (! $pengguna->buku_kas()->exists() || ! $pengguna->dompet()->exists()) {
                $pengguna->forceFill(['paket_langganan_dipilih_id' => $paketAktif->id])->save();

                return redirect()->to(Onboarding::getUrl());
            }

            // Pengguna login yang sudah onboarding langsung ke Langganan dengan modal terbuka.
            return redirect()->to(LanggananResource::getUrl('index', ['paket' => $paketAktif->id]));
        }

        // Tamu: ingat pilihan di session lalu arahkan ke registrasi.
        $request->session()->put(self::SESSION_KEY, $paketAktif->id);

        return redirect()->to(filament()->getRegistrationUrl() ?? route('filament.admin.auth.register'));
    }
}
