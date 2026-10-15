<?php

namespace App\Http\Middleware;

use App\Filament\Pages\Onboarding;
use App\Http\Controllers\BeliPaketController;
use App\Models\PaketLangganan;
use App\Services\PastikanAkunKeuanganDefault;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompletedOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Pilihan paket dari landing page yang dibawa lewat session (mis. tamu yang akhirnya masuk
        // dengan akun lama) dipindahkan ke kolom users agar terbawa sampai ke halaman Langganan.
        if ($user && ! $user->isAdmin() && $request->session()->has(BeliPaketController::SESSION_KEY)) {
            $paketId = $request->session()->pull(BeliPaketController::SESSION_KEY);

            if (is_numeric($paketId) && PaketLangganan::query()->where('is_active', true)->whereKey((int) $paketId)->exists()) {
                $user->forceFill(['paket_langganan_dipilih_id' => (int) $paketId])->save();
            }
        }

        if (
            $user
            && ! $user->isAdmin()
            && ($user->buku_kas()->exists() || $user->dompet()->exists())
        ) {
            app(PastikanAkunKeuanganDefault::class)->jalankan($user);
        }

        // Pengguna baru yang belum verifikasi email harus menyelesaikan alur verifikasi email terlebih dahulu
        // agar tidak terjadi redirect loop antara halaman onboarding dan prompt verifikasi email.
        if (
            $user
            && ! $user->isAdmin()
            && (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail())
            && (! $user->buku_kas()->exists() || ! $user->dompet()->exists())
            && ! $request->routeIs('filament.admin.pages.onboarding')
            && ! $request->routeIs('filament.admin.auth.*')
        ) {
            return redirect(Onboarding::getUrl());
        }

        return $next($request);
    }
}
