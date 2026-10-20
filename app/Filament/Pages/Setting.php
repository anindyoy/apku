<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Pengaturan;
use App\Services\PengaturanHargaEmas;
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

class Setting extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Pengaturan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'Harga Emas';

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
            app(PengaturanHargaEmas::class)->tersimpan(),
            ['tabungan_emas_aktif' => app(PengaturanTabunganEmas::class)->aktif()],
        ));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Aktivasi fitur')
                ->description('Pengaturan aktivasi fitur tabungan emas untuk pengguna non-admin.')
                ->schema([
                    Toggle::make('tabungan_emas_aktif')
                        ->label('Fitur tabungan emas user aktif')
                        ->helperText('Jika nonaktif, menu dan aksi tabungan emas disembunyikan dari pengguna.')
                        ->default(false),
                ]),
            Section::make('Harga emas')
                ->description('Berlaku untuk seluruh pengguna. Kosongkan kolom untuk menggunakan nilai default yang ditampilkan.')
                ->schema([
                    TextInput::make('url')->label('URL API harga emas')
                        ->placeholder(config('services.harga_emas.url'))
                        ->url()->rules(['url:http,https'])->maxLength(2048)
                        ->helperText('Alamat layanan pengambilan harga buyback dengan format respons yang didukung aplikasi.')
                        ->columnSpanFull(),
                    TextInput::make('source')->label('URL sumber harga emas')
                        ->placeholder(config('services.harga_emas.source'))
                        ->url()->rules(['url:http,https'])->maxLength(2048)
                        ->helperText('Tautan pada label sumber di modal Cek Nilai Emas.')
                        ->columnSpanFull(),
                    TextInput::make('timeout')->label('Batas waktu permintaan')
                        ->placeholder((string) config('services.harga_emas.timeout'))
                        ->numeric()->rules(['integer'])->minValue(1)->maxValue(60)->suffix('detik'),
                    TextInput::make('cache_hours')->label('Durasi cache harga')
                        ->placeholder((string) config('services.harga_emas.cache_hours'))
                        ->numeric()->rules(['integer'])->minValue(1)->maxValue(168)->suffix('jam'),
                ])->columns(2),
        ])->statePath('data');
    }

    public function simpan(): void
    {
        abort_unless(static::canAccess(), 403);
        $state = $this->form->getState();
        app(PengaturanHargaEmas::class)->simpan(auth()->user(), $state);
        if (array_key_exists('tabungan_emas_aktif', $state)) {
            app(PengaturanTabunganEmas::class)->simpan(auth()->user(), [
                'aktif' => (bool) $state['tabungan_emas_aktif'],
            ]);
        }
        Notification::make()->title('Pengaturan harga emas disimpan')->success()->send();
    }
}
