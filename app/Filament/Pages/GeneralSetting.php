<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Pengaturan;
use App\Services\PengaturanKontak;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

// Pengaturan umum aplikasi yang tidak terkait dengan fitur spesifik seperti harga emas.
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
        $this->form->fill(app(PengaturanKontak::class)->tersimpan());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
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
        app(PengaturanKontak::class)->simpan(auth()->user(), $this->form->getState());
        Notification::make()->title('Pengaturan disimpan')->success()->send();
    }
}
