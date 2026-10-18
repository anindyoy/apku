<?php

namespace App\Filament\Pages;

use App\Filament\Forms\KomponenNominal;
use App\Filament\Resources\LanggananResource;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\PaketLangganan;
use App\Services\KategoriService;
use App\Services\TransaksiService;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class Onboarding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'onboarding';

    protected string $view = 'filament.pages.onboarding';

    public ?array $data = [];

    // Menghilangkan header label heading halaman agar tidak memunculkan header default Filament di halaman onboarding.
    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function mount(): void
    {
        if (auth()->user()->buku_kas()->exists() && auth()->user()->dompet()->exists()) {
            $this->redirect($this->tujuanSetelahOnboarding(), navigate: true);

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Kas Utama')
                    ->description('Atur kas pertama Anda')
                    ->icon('heroicon-o-book-open')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_buku')
                            ->label('Label kas utama')
                            ->placeholder('Contoh: Keuangan Harian atau Rekening Usaha')
                            ->default('Kas Utama')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('nama_dompet')
                            ->label('Label sumber dana utama')
                            ->default('Cash')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Contoh: Tunai, BCA, DANA'),
                        Textarea::make('description')
                            ->label('Deskripsi kas')
                            ->placeholder('Contoh: Catatan pemasukan dan pengeluaran sehari-hari')
                            ->rows(3)
                            ->maxLength(200),
                        KomponenNominal::terapkan(
                            TextInput::make('saldo_awal')
                                ->label('Saldo awal')
                                ->placeholder('Contoh: 1.000.000')
                                ->required()
                                ->minValue(0)
                        ),
                    ]),
                Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Section::make('Kategori Pemasukan')
                            ->description('Tambahkan kategori yang sering digunakan')
                            ->icon('heroicon-o-arrow-trending-up')
                            ->schema([
                                Repeater::make('kategori_pemasukan')
                                    ->label('Kategori pemasukan')
                                    ->simple(
                                        TextInput::make('nama')
                                            ->placeholder('Contoh: Gaji, Bonus, atau Penjualan')
                                            ->required()
                                            ->maxLength(255)
                                    )
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->maxItems(10)
                                    ->reorderable(false)
                                    ->deletable(fn (Repeater $component): bool => count($component->getState() ?? []) > 1)
                                    ->addActionLabel('Tambah kategori pemasukan'),
                            ]),
                        Section::make('Kategori Pengeluaran')
                            ->description('Lengkapi kategori pengeluaran Anda')
                            ->icon('heroicon-o-arrow-trending-down')
                            ->schema([
                                Repeater::make('kategori_pengeluaran')
                                    ->label('Kategori pengeluaran')
                                    ->simple(
                                        TextInput::make('nama')
                                            ->placeholder('Contoh: Makan, Transportasi, atau Tagihan')
                                            ->required()
                                            ->maxLength(255)
                                    )
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->maxItems(10)
                                    ->reorderable(false)
                                    ->deletable(fn (Repeater $component): bool => count($component->getState() ?? []) > 1)
                                    ->addActionLabel('Tambah kategori pengeluaran'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $simpanPengaturan = function () use ($data, $user): void {
            $bukuKas = BukuKas::create([
                'user_id' => $user->id,
                'nama_buku' => $data['nama_buku'],
                'description' => $data['description'] ?: null,
                'saldo' => 0,
                'is_default' => true,
            ]);

            $dompet = Dompet::create([
                'user_id' => $user->id,
                'nama_dompet' => $data['nama_dompet'],
                'jenis' => 'tunai',
                'saldo' => 0,
                'is_default' => true,
                // Komentar kode boleh menyebut istilah lama; teks UI memakai sumber dana.
                'description' => 'Sumber dana utama',
            ]);

            app(TransaksiService::class)->buatSaldoAwal(
                $user,
                $bukuKas,
                $dompet,
                (int) $data['saldo_awal'],
            );

            foreach (['Pemasukan' => 'kategori_pemasukan', 'Pengeluaran' => 'kategori_pengeluaran'] as $tipe => $field) {
                foreach ($data[$field] as $kategori) {
                    // Kategori awal langsung dihubungkan ke kas utama.
                    app(KategoriService::class)->buat($user, [
                        'tipe' => $tipe,
                        'nama' => is_array($kategori) ? $kategori['nama'] : $kategori,
                    ], [$bukuKas->id]);
                }
            }
        };

        if (DB::transactionLevel() > 0) {
            $simpanPengaturan();
        } else {
            DB::transaction($simpanPengaturan);
        }

        Notification::make()
            ->title('Pengaturan awal berhasil disimpan')
            ->success()
            ->send();

        $this->redirect($this->tujuanSetelahOnboarding(), navigate: true);
    }

    // Setelah onboarding, pengguna dengan paket tersimpan langsung tiba di Langganan dengan modal terbuka.
    protected function tujuanSetelahOnboarding(): string
    {
        $pengguna = auth()->user();
        $paketId = $pengguna?->paket_langganan_dipilih_id;

        // Paket bisa dinonaktifkan admin selama onboarding; dalam hal ini pakai tujuan default.
        if (is_numeric($paketId) && PaketLangganan::query()->where('is_active', true)->whereKey((int) $paketId)->exists()) {
            return LanggananResource::getUrl('index', ['paket' => (int) $paketId]);
        }

        return filament()->getUrl();
    }
}
