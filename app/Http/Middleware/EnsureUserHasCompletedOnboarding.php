<?php

namespace App\Http\Middleware;

use App\Filament\Pages\Onboarding;
use App\Http\Controllers\BeliPaketController;
use App\Models\PaketLangganan;
use App\Services\PastikanAkunKeuanganDefault;
use Closure;
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

        if (
            $user
            && ! $user->isAdmin()
            && (! $user->buku_kas()->exists() || ! $user->dompet()->exists())
            && ! $request->routeIs('filament.admin.pages.onboarding')
        ) {
            return redirect(Onboarding::getUrl());
        }

        return $next($request);
    }
}
