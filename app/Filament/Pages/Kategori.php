<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Pengaturan;
use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Forms\KategoriFormSchema;
use App\Models\BukuKas;
use App\Models\Kategori as KategoriModel;
use App\Models\Transaksi;
use App\Services\KategoriService;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class Kategori extends Page implements HasTable
{
    use HidesFromAdminNavigation;
    use InteractsWithTable;

    protected static ?string $cluster = Pengaturan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Kategori';

    protected static ?string $title = 'Kategori';

    protected string $view = 'filament.pages.kategori';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pengaturanTampilan')
                ->label('Pengaturan tampilan')
                ->icon('heroicon-o-adjustments-horizontal')
                ->modalHeading('Pengaturan tampilan kategori')
                ->modalSubmitActionLabel('Simpan pengaturan')
                ->modalWidth('md')
                ->form(fn (): array => [
                    Toggle::make('pisahkan_tipe')
                        ->label('Pisahkan kategori berdasarkan tipe transaksi')
                        ->helperText('Aktif: dropdown kategori pada transaksi difilter sesuai Pemasukan/Pengeluaran, dan kategori baru default Pengeluaran. Nonaktif: seluruh kategori ditampilkan tanpa filter, dan kategori baru default Semua. Kategori yang sudah ada tidak berubah.'),
                ])
                ->fillForm(fn (): array => ['pisahkan_tipe' => auth()->user()->pisahkanTipeKategori()])
                ->action(function (array $data): void {
                    auth()->user()->forceFill(['pisahkan_tipe_kategori' => (bool) $data['pisahkan_tipe']])->save();
                    Notification::make()->title('Pengaturan kategori disimpan')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => KategoriModel::query()
                ->with(['kas', 'user:id,name'])
                ->withCount('transaksi'))
            ->defaultSort('nama')
            ->emptyStateHeading('Belum ada kategori')
            ->emptyStateDescription('Kategori bersifat opsional. Tambahkan kategori lalu hubungkan ke kas yang memakainya.')
            ->columns([
                TextColumn::make('nama')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (KategoriModel $record): string => static::keteranganKategori($record)),

                TextColumn::make('tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pemasukan' => 'success',
                        'Pengeluaran' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('kas.nama_buku')
                    ->label('Kas')
                    ->badge()
                    ->color('info')
                    ->placeholder('Belum terhubung ke kas')
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('kas')
                    ->label('Kas')
                    ->options(fn (): array => BukuKas::query()->pluck('nama_buku', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('kas', fn (Builder $query) => $query->whereKey($data['value']))
                        : $query),

                SelectFilter::make('tipe')
                    ->options(array_combine(KategoriModel::TIPE, KategoriModel::TIPE)),
            ])
            ->headerActions([
                Action::make('tambah')
                    ->label('Tambah kategori')
                    ->icon('heroicon-o-plus')
                    ->visible(fn (): bool => auth()->user()->can('create', KategoriModel::class) && $this->opsiKas() !== [])
                    ->modalHeading('Tambah kategori')
                    ->modalWidth('md')
                    ->form(fn (): array => $this->formKategori())
                    ->fillForm(fn (): array => [
                        'tipe' => auth()->user()->pisahkanTipeKategori() ? 'Pengeluaran' : 'Semua',
                        'kas' => count($this->opsiKas()) === 1 ? array_keys($this->opsiKas()) : [],
                    ])
                    ->action(fn (array $data, Action $action) => $this->jalankan($action, function () use ($data): string {
                        app(KategoriService::class)->buat(auth()->user(), $data, $data['kas'] ?? []);

                        return 'Kategori berhasil ditambahkan';
                    })),
            ])
            ->actions([
                Action::make('ubah')
                    ->label('Ubah')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (KategoriModel $record): bool => auth()->user()->can('update', $record))
                    ->modalHeading(fn (KategoriModel $record): string => 'Ubah '.$record->nama)
                    ->modalWidth('md')
                    ->form(fn (KategoriModel $record): array => $this->formKategori($record))
                    ->fillForm(fn (KategoriModel $record): array => [
                        'nama' => $record->nama,
                        'tipe' => $record->tipe,
                        'kas' => array_values(array_intersect($record->idKas(), array_keys($this->opsiKas($record)))),
                    ])
                    ->action(fn (KategoriModel $record, array $data, Action $action) => $this->jalankan($action, function () use ($record, $data): string {
                        app(KategoriService::class)->ubah(auth()->user(), $record, $data, $data['kas'] ?? []);

                        return 'Kategori berhasil diperbarui';
                    })),

                Action::make('lepasDariKas')
                    ->label('Lepas dari kas')
                    ->icon('heroicon-o-link-slash')
                    ->color('gray')
                    ->visible(fn (KategoriModel $record): bool => $this->opsiKasTerhubung($record) !== [])
                    ->modalHeading(fn (KategoriModel $record): string => 'Lepas '.$record->nama.' dari kas')
                    ->modalDescription('Kategori tidak lagi dapat dipilih pada kas tersebut. Jika masih dipakai transaksi di kas itu, centang pilihan kosongkan agar transaksinya menjadi tanpa kategori.')
                    ->modalSubmitActionLabel('Lepas')
                    ->modalWidth('md')
                    ->form(fn (KategoriModel $record): array => [
                        Select::make('buku_kas_id')
                            ->label('Kas')
                            ->options($this->opsiKasTerhubung($record))
                            ->searchable(false)
                            ->required(),
                        Checkbox::make('kosongkan')
                            ->label('Kosongkan kategori pada transaksi kas ini yang masih memakainya'),
                    ])
                    ->action(fn (KategoriModel $record, array $data, Action $action) => $this->jalankan($action, function () use ($record, $data): string {
                        $jumlah = app(KategoriService::class)->lepasDariKas(
                            auth()->user(),
                            $record,
                            BukuKas::findOrFail($data['buku_kas_id']),
                            (bool) ($data['kosongkan'] ?? false),
                        );

                        return $jumlah > 0
                            ? 'Kategori dilepas dan '.number_format($jumlah, 0, ',', '.').' transaksi dikosongkan'
                            : 'Kategori dilepas dari kas';
                    })),

                Action::make('hapus')
                    ->label('Hapus')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->visible(fn (KategoriModel $record): bool => auth()->user()->can('delete', $record))
                    ->requiresConfirmation()
                    ->modalHeading(fn (KategoriModel $record): string => 'Hapus '.$record->nama)
                    ->modalDescription(fn (KategoriModel $record): string => $record->transaksi()->withoutGlobalScopes()->exists()
                        ? 'Kategori ini masih dipakai transaksi. Pilih kategori pengganti, atau biarkan kosong agar transaksinya menjadi tanpa kategori.'
                        : 'Kategori yang dihapus tidak dapat dikembalikan.')
                    ->form(fn (KategoriModel $record): array => [
                        Select::make('pengganti')
                            ->label('Kategori pengganti')
                            ->placeholder('Jadikan tanpa kategori')
                            ->options($this->opsiPengganti($record))
                            ->visible($record->transaksi()->withoutGlobalScopes()->exists()),
                    ])
                    ->action(fn (KategoriModel $record, array $data, Action $action) => $this->jalankan($action, function () use ($record, $data): string {
                        app(KategoriService::class)->hapus(
                            auth()->user(),
                            $record,
                            filled($data['pengganti'] ?? null) ? (int) $data['pengganti'] : null,
                        );

                        return 'Kategori berhasil dihapus';
                    })),
            ]);
    }

    public static function keteranganKategori(KategoriModel $kategori): string
    {
        $keterangan = number_format((int) $kategori->transaksi_count, 0, ',', '.').' transaksi';

        return $kategori->user_id === auth()->id()
            ? $keterangan
            : $keterangan.' · Milik '.($kategori->user?->name ?? '-');
    }

    /** @return array<int, mixed> */
    private function formKategori(?KategoriModel $kategori = null): array
    {
        return KategoriFormSchema::fields(
            fn (): array => $this->opsiKas($kategori),
            $kategori === null,
        );
    }

    /**
     * Kas yang kategorinya dapat dikelola pengguna: kas sendiri dan kas bersama sebagai Editor.
     *
     * @return array<int, string>
     */
    private function opsiKas(?KategoriModel $kategori = null): array
    {
        $user = auth()->user();

        return BukuKas::query()->with('user:id,name')->get()
            ->filter(fn (BukuKas $kas): bool => $user->dapatMengelolaKategoriPada($kas)
                && ($kategori === null || $kas->user_id === $kategori->user_id))
            ->mapWithKeys(fn (BukuKas $kas): array => [
                $kas->id => $kas->user_id === $user->id
                    ? $kas->nama_buku
                    : $kas->nama_buku.' (kas bersama '.($kas->user?->name ?? '-').')',
            ])
            ->all();
    }

    /** @return array<int, string> */
    private function opsiKasTerhubung(KategoriModel $kategori): array
    {
        return array_intersect_key($this->opsiKas($kategori), array_flip($kategori->idKas()));
    }

    /**
     * Kategori pengganti harus terhubung ke semua kas yang transaksinya memakai kategori ini.
     *
     * @return array<int, string>
     */
    private function opsiPengganti(KategoriModel $kategori): array
    {
        $kasTerpakai = Transaksi::withoutGlobalScopes()
            ->where('kategori_id', $kategori->id)
            ->distinct()
            ->pluck('buku_kas_id');

        return KategoriModel::withoutGlobalScopes()
            ->where('user_id', $kategori->user_id)
            ->whereKeyNot($kategori->id)
            ->orderBy('nama')
            ->get()
            ->filter(fn (KategoriModel $calon): bool => $kasTerpakai->diff($calon->idKas())->isEmpty())
            ->mapWithKeys(fn (KategoriModel $calon): array => [$calon->id => $calon->nama.' ('.$calon->tipe.')'])
            ->all();
    }

    /** Menjalankan aksi kategori dan menampilkan pesan validasi sebagai notifikasi. */
    private function jalankan(Action $action, Closure $proses): void
    {
        try {
            $judul = $proses();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?? 'Data kategori tidak valid.')
                ->danger()
                ->send();
            $action->halt();
        } catch (AuthorizationException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
            $action->halt();
        }

        Notification::make()->title($judul)->success()->send();
    }
}
