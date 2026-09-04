<?php

namespace App\Filament\Pages;

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Services\TransaksiService;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class Onboarding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'onboarding';

    protected string $view = 'filament.pages.onboarding';

    public ?array $data = [];

    public function mount(): void
    {
        if (auth()->user()->buku_kas()->exists() && auth()->user()->dompet()->exists()) {
            $this->redirect(filament()->getUrl(), navigate: true);

            return;
        }

        $this->form->fill([
            'nama_buku' => 'Kas Utama',
            'nama_dompet' => 'Cash',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Wizard::make([
                    Step::make('Buku Kas Utama')
                        ->description('Atur buku kas pertama Anda')
                        ->icon('heroicon-o-book-open')
                        ->schema([
                            TextInput::make('nama_buku')
                                ->label('Label buku kas utama')
                                ->placeholder('Contoh: Dompet Harian atau Rekening Usaha')
                                ->required()
                                ->maxLength(50),
                            TextInput::make('nama_dompet')
                                ->label('Label dompet utama')
                                ->required()
                                ->maxLength(50),
                            Textarea::make('description')
                                ->label('Deskripsi buku kas')
                                ->placeholder('Contoh: Catatan pemasukan dan pengeluaran sehari-hari')
                                ->rows(3)
                                ->maxLength(200),
                            TextInput::make('saldo_awal')
                                ->label('Saldo awal')
                                ->placeholder('0')
                                ->prefix('Rp')
                                ->required()
                                ->numeric()
                                ->minValue(0),
                        ]),
                    Step::make('Kategori Pemasukan')
                        ->description('Tambahkan kategori yang sering digunakan')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->schema([
                            Repeater::make('kategori_pemasukan')
                                ->label('Kategori pemasukan')
                                ->simple(
                                    TextInput::make('nama_jenis')
                                        ->placeholder('Contoh: Gaji, Bonus, atau Penjualan')
                                        ->required()
                                        ->maxLength(255)
                                )
                                ->defaultItems(2)
                                ->minItems(2)
                                ->maxItems(10)
                                ->addActionLabel('Tambah kategori pemasukan'),
                        ]),
                    Step::make('Kategori Pengeluaran')
                        ->description('Lengkapi kategori pengeluaran Anda')
                        ->icon('heroicon-o-arrow-trending-down')
                        ->schema([
                            Repeater::make('kategori_pengeluaran')
                                ->label('Kategori pengeluaran')
                                ->simple(
                                    TextInput::make('nama_jenis')
                                        ->placeholder('Contoh: Makan, Transportasi, atau Tagihan')
                                        ->required()
                                        ->maxLength(255)
                                )
                                ->defaultItems(2)
                                ->minItems(2)
                                ->maxItems(10)
                                ->addActionLabel('Tambah kategori pengeluaran'),
                        ]),
                ])
                    ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-color-primary fi-color-primary fi-size-md">Mulai menggunakan APKu</button>')),
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
                'saldo' => 0,
                'is_default' => true,
                'description' => 'Dompet utama',
            ]);

            app(TransaksiService::class)->buatSaldoAwal(
                $user,
                $bukuKas,
                $dompet,
                (int) $data['saldo_awal'],
            );

            foreach (['Pemasukan' => 'kategori_pemasukan', 'Pengeluaran' => 'kategori_pengeluaran'] as $tipe => $field) {
                foreach ($data[$field] as $kategori) {
                    JenisTransaksi::create([
                        'user_id' => $user->id,
                        'tipe' => $tipe,
                        'nama_jenis' => is_array($kategori) ? $kategori['nama_jenis'] : $kategori,
                    ]);
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

        $this->redirect(filament()->getUrl(), navigate: true);
    }
}
