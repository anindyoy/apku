<?php

namespace App\Providers;

use App\Services\DashboardCache;
use Filament\Tables\Table;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setLocale('id');

        // Arahkan tamu yang membuka halaman non-Filament ke login admin karena tidak ada route login bawaan.
        Authenticate::redirectUsing(fn (Request $request): ?string => route('filament.admin.auth.login'));

        DB::listen(DashboardCache::invalidateWrite(...));

        Table::configureUsing(function (Table $table): void {
            if (! app()->runningUnitTests()) {
                $table->deferLoading();
            }

            $table->striped();
        });

        // Kustomisasi email verifikasi pendaftaran dengan logo aplikasi dan tampilan khas APKu.
        VerifyEmail::toMailUsing(function ($notifiable, string $url): MailMessage {
            $nama = $notifiable->name ?? 'Pengguna APKu';

            return (new MailMessage)
                ->subject(__('Verifikasi alamat email Anda'))
                ->greeting("Halo, {$nama}!")
                ->line(__('Pilih tombol di bawah untuk memverifikasi alamat email Anda.'))
                ->action(__('Verifikasi Alamat Email'), $url)
                ->line(__('Jika Anda tidak membuat akun, tidak ada tindakan lebih lanjut yang diperlukan.'))
                ->view([
                    'html' => 'emails.verifikasi-email',
                    'text' => 'emails.verifikasi-email-text',
                ], [
                    'url' => $url,
                    'user' => $notifiable,
                    'nama' => $nama,
                ]);
        });
    }
}
