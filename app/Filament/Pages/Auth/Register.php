<?php

namespace App\Filament\Pages\Auth;

use App\Rules\TurnstileToken;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
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
                $this->getTurnstileFormComponent(),
            ]);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            return parent::register();
        } finally {
            $this->dispatch('turnstile-reset');
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
