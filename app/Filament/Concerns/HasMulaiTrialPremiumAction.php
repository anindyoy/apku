<?php

namespace App\Filament\Concerns;

use App\Services\MulaiTrialPremium;
use App\Services\PengaturanTrial;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

// Aksi mulai coba gratis Premium yang dipakai ulang di Dashboard, Langganan, dan Akun Saya.
trait HasMulaiTrialPremiumAction
{
    public function mulaiTrialPremiumAction(): Action
    {
        $durasi = app(PengaturanTrial::class)->durasiHari();
        $berakhir = today()->copy()->addDays($durasi - 1)->translatedFormat('d F Y');

        return Action::make('mulaiTrialPremium')
            ->label('Mulai coba gratis '.$durasi.' hari')
            ->icon('heroicon-o-sparkles')
            ->color('success')
            ->visible(fn (): bool => auth()->check() && ! auth()->user()->isAdmin() && app(MulaiTrialPremium::class)->layak(auth()->user()))
            ->requiresConfirmation()
            ->modalHeading('Mulai coba gratis Premium?')
            ->modalDescription('Anda mendapat akses Premium penuh selama '.$durasi.' hari sampai '.$berakhir.'. Termasuk kas dan dompet tanpa batas, kolaborasi kas, dan tautan kas publik. Catatan: kas dan dompet ke-3 dan seterusnya tidak dapat dikelola lagi setelah trial berakhir, tetapi datanya tetap tersimpan.')
            ->modalSubmitActionLabel('Mulai trial')
            ->action(function (): void {
                try {
                    app(MulaiTrialPremium::class)->handle(auth()->user());
                    Notification::make()->title('Coba gratis Premium aktif')->success()->send();
                } catch (ValidationException $e) {
                    // Tampilkan alasan penolakan pertama agar pengguna tahu syarat yang kurang.
                    $pesan = collect($e->errors())->flatten()->first() ?? 'Tidak dapat memulai coba gratis.';
                    Notification::make()->title('Tidak dapat memulai trial')->body($pesan)->danger()->send();
                }
            });
    }

    // Ringkasan kelayakan untuk kartu CTA dan panduan di Blade.
    public function infoTrialPremium(): array
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin()) {
            return ['layak' => false, 'alasan' => null, 'trial' => null, 'durasi' => app(PengaturanTrial::class)->durasiHari()];
        }

        $layanan = app(MulaiTrialPremium::class);

        return [
            'layak' => $layanan->layak($user),
            'alasan' => $layanan->alasanTidakLayak($user),
            'trial' => $user->trialPremiumAktif(),
            'durasi' => app(PengaturanTrial::class)->durasiHari(),
        ];
    }
}
