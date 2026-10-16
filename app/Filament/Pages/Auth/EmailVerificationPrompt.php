<?php

namespace App\Filament\Pages\Auth;

use App\Services\PengaturanKontak;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * @property-read Action $logoutAction
 * @property-read Action $resendNotificationAction
 */
class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    public function content(Schema $schema): Schema
    {
        $email = filament()->auth()->user()?->getEmailForVerification();
        $pesanWa = filled($email)
            ? "Halo Admin APKu, saya butuh bantuan verifikasi email akun saya ({$email})."
            : 'Halo Admin APKu, saya butuh bantuan verifikasi email akun saya.';

        $urlWa = app(PengaturanKontak::class)->urlWa($pesanWa);

        $teksResend = __('filament-panels::auth/pages/email-verification/email-verification-prompt.messages.notification_not_received') .
            ' ' .
            $this->resendNotificationAction->toHtml();

        $komponenEkstra = [];

        // Teks penanya kendala dan tombol bantuan WhatsApp admin hanya tampil jika nomor sudah diatur.
        if ($urlWa !== null) {
            $komponenEkstra[] = Text::make(new HtmlString(
                'Ada masalah verifikasi email? ' .
                '<a href="' . e($urlWa) . '" target="_blank" rel="noopener noreferrer" ' .
                'class="text-sm text-primary-600 hover:underline">' .
                '💬 Hubungi admin via WhatsApp' .
                '</a>',
            ));
        }

        $komponenEkstra[] = Text::make(new HtmlString(
            '<div class="flex items-center justify-between gap-4 mt-2">' .
                '<a href="/" class="text-sm text-gray-600 hover:underline">← Kembali ke halaman utama</a>' .
                $this->logoutAction->toHtml() .
            '</div>',
        ));

        return $schema->components([
            Text::make(__('filament-panels::auth/pages/email-verification/email-verification-prompt.messages.notification_sent', [
                'email' => filament()->auth()->user()?->getEmailForVerification(),
            ])),
            Text::make(new HtmlString($teksResend)),
            ...$komponenEkstra,
        ]);
    }

    // Aksi keluar agar pengguna yang salah memasukkan email saat registrasi dapat meninggalkan halaman ini.
    public function logoutAction(): Action
    {
        return Action::make('logout')
            ->link()
            ->label('Keluar dari akun ini')
            ->size('sm')
            ->color('danger')
            ->action(function (): void {
                try {
                    $this->rateLimit(5);
                } catch (TooManyRequestsException) {
                    return;
                }

                Filament::auth()->logout();

                session()->invalidate();
                session()->regenerateToken();

                redirect()->to(Filament::getLoginUrl());
            });
    }
}
