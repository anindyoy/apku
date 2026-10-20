<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Pengaturan;
use App\Services\PengaturanKontak;
use App\Services\PengaturanTabunganEmas;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

// Pengaturan umum aplikasi untuk kontak dan aktivasi fitur.
class GeneralSetting extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Pengaturan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $title = 'General Setting';

    protected static ?string $navigationLabel = 'General Setting';

    protected string $view = 'filament.pages.setting';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill(array_merge(
            app(PengaturanKontak::class)->tersimpan(),
            ['tabungan_emas_aktif' => app(PengaturanTabunganEmas::class)->aktif()],
        ));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Fitur tabungan emas')
                ->description('Pengaturan aktivasi fitur tabungan emas untuk pengguna non-admin.')
                ->schema([
                    Toggle::make('tabungan_emas_aktif')
                        ->label('Fitur tabungan emas user aktif')
                        ->helperText('Jika nonaktif, menu dan aksi tabungan emas disembunyikan dari pengguna.')
                        ->default(false),
                ]),
            Section::make('Kontak admin')
                ->description('Nomor yang tampil di halaman verifikasi email sebagai tombol bantuan WhatsApp.')
                ->schema([
                    TextInput::make('wa')
                        ->label('Nomor WhatsApp admin')
                        ->placeholder('628123456789')
                        ->helperText('Masukkan nomor internasional tanpa tanda + (contoh: 628123456789).')
                        ->tel()
                        ->maxLength(20),
                ]),
        ])->statePath('data');
    }

    public function simpan(): void
    {
        abort_unless(static::canAccess(), 403);
        $state = $this->form->getState();
        app(PengaturanKontak::class)->simpan(auth()->user(), $state);
        app(PengaturanTabunganEmas::class)->simpan(auth()->user(), [
            'aktif' => (bool) ($state['tabungan_emas_aktif'] ?? false),
        ]);
        Notification::make()->title('Pengaturan disimpan')->success()->send();
    }
}
