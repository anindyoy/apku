<?php

namespace App\Filament\Pages\Auth;

use App\Http\Controllers\BeliPaketController;
use App\Models\PaketLangganan;
use App\Rules\TurnstileToken;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                ... (config('app.env') === 'production' ? [$this->getTurnstileFormComponent()] : []),
            ]);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $response = parent::register();
        } finally {
            if (config('app.env') === 'production') {
                $this->dispatch('turnstile-reset');
            }
        }

        if ($response) {
            $this->pindahkanPaketDipilihKePengguna();
        }

        return $response;
    }

    // Memindahkan pilihan paket dari session ke kolom users agar terbawa melewati verifikasi email di perangkat lain.
    protected function pindahkanPaketDipilihKePengguna(): void
    {
        $paketId = session()->pull(BeliPaketController::SESSION_KEY);

        if (! is_numeric($paketId)) {
            return;
        }

        $pengguna = Filament::auth()->user() ?? auth()->user();

        if (! $pengguna) {
            return;
        }

        // Paket bisa dinonaktifkan admin selama tamu mengisi formulir; abaikan tanpa menggagalkan registrasi.
        $masihAktif = PaketLangganan::query()->where('is_active', true)->whereKey((int) $paketId)->exists();

        if ($masihAktif) {
            $pengguna->forceFill(['paket_langganan_dipilih_id' => (int) $paketId])->save();
        }
    }

    protected function getTurnstileFormComponent(): Component
    {
        return ViewField::make('turnstile_token')
            ->label('Verifikasi keamanan')
            ->view('filament.forms.components.turnstile')
            ->viewData([
                'siteKey' => config('services.turnstile.site_key'),
                'action' => config('services.turnstile.action'),
            ])
            ->required()
            ->rules(['string', new TurnstileToken])
            ->dehydrated(false);
    }
}
