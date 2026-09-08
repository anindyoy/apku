<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\TransaksiResource;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Services\OpsiSelectCache;
use App\Services\TransaksiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PencarianTransaksi extends Page implements HasTable
{
    use HidesFromAdminNavigation;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Cari Transaksi';

    protected static ?string $title = 'Pencarian Global Transaksi';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.pencarian-transaksi';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = Transaksi::query()->with([
                    'buku_kas:id,user_id,nama_buku',
                    'jenis_transaksi:id,nama_jenis',
                    'asal_buku_tabungan:id,nama_buku',
                    'tujuan_buku_tabungan:id,nama_buku',
                    'user:id,name',
                    'dompet' => fn ($query) => $query->withTrashed(),
                ]);

                return filled($this->getTableSearch())
                    ? $query
                    : $query->whereRaw('1 = 0');
            })
            ->searchPlaceholder('Cari deskripsi, aktivitas, kas, tipe, nominal, atau pengguna...')
            ->emptyStateHeading(fn (): string => filled($this->getTableSearch())
                ? 'Transaksi tidak ditemukan'
                : 'Masukkan kata pencarian')
            ->emptyStateDescription(fn (): string => filled($this->getTableSearch())
                ? 'Coba gunakan kata pencarian atau filter yang berbeda.'
                : 'Tabel akan menampilkan hasil setelah pencarian dilakukan.')
            ->emptyStateIcon('heroicon-o-magnifying-glass')
            ->columns([
                IconColumn::make('jenis')
                    ->label('Tipe')
                    ->searchable()
                    ->tooltip(fn (string $state): string => $state)
                    ->icon(fn (string $state): string => match ($state) {
                        'Pemasukan' => 'heroicon-o-arrow-down-on-square',
                        'Pengeluaran' => 'heroicon-o-arrow-up-on-square',
                        'Transfer Pemasukan', 'Transfer Pengeluaran' => 'heroicon-o-arrow-path-rounded-square',
                    })
                    ->color(fn (string $state): string => TransaksiResource::getWarnaTipeTransaksi($state)),

                TextColumn::make('tanggal')
                    ->formatStateUsing(fn ($state) => date('d M Y, H:i', strtotime($state)))
                    ->description(fn (Transaksi $record): string => 'Dicatat oleh: '.($record->user?->name ?? '-'))
                    ->sortable()
                    ->color(fn (Transaksi $record): string => TransaksiResource::getWarnaTipeTransaksi($record->jenis)),

                TextColumn::make('buku_kas.nama_buku')
                    ->label('Kas')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Transaksi $record): string => 'Dompet: '.$record->labelDompetUntuk(auth()->user()))
                    ->color(fn (Transaksi $record): string => TransaksiResource::getWarnaTipeTransaksi($record->jenis)),

                TextColumn::make('kategori')
                    ->label('Aktivitas')
                    ->getStateUsing(fn (Transaksi $record): ?string => TransaksiResource::getKategoriLabel($record))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query
                                ->where('deskripsi', 'like', "%{$search}%")
                                ->orWhereHas('jenis_transaksi', fn (Builder $query) => $query->where('nama_jenis', 'like', "%{$search}%"))
                                ->orWhereHas('asal_buku_tabungan', fn (Builder $query) => $query->where('nama_buku', 'like', "%{$search}%"))
                                ->orWhereHas('tujuan_buku_tabungan', fn (Builder $query) => $query->where('nama_buku', 'like', "%{$search}%"))
                                ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->description(fn (Transaksi $record): string => $record->deskripsi ? 'Deskripsi: '.$record->deskripsi : '')
                    ->color(fn (Transaksi $record): string => TransaksiResource::getWarnaTipeTransaksi($record->jenis))
                    ->wrap(),

                TextColumn::make('nominal')
                    ->numeric()
                    ->prefix('Rp ')
                    ->searchable()
                    ->sortable()
                    ->color(fn (Transaksi $record): string => TransaksiResource::getWarnaTipeTransaksi($record->jenis)),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Tipe transaksi')
                    ->options([
                        'Pemasukan' => 'Pemasukan',
                        'Pengeluaran' => 'Pengeluaran',
                        'Transfer Pemasukan' => 'Transfer Pemasukan',
                        'Transfer Pengeluaran' => 'Transfer Pengeluaran',
                    ]),

                SelectFilter::make('buku_kas_id')
                    ->label('Kas')
                    ->options(fn (): array => OpsiSelectCache::ingat('buku-kas', fn (): array => BukuKas::query()
                        ->pluck('nama_buku', 'id')
                        ->all(), auth()->id()))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('dompet_id')
                    ->label('Dompet')
                    ->options(fn (): array => OpsiSelectCache::ingat('dompet', fn (): array => Dompet::query()
                        ->pluck('nama_dompet', 'id')
                        ->all(), auth()->id(), 'aktif'))
                    ->searchable()
                    ->preload(),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Action::make('edit')
                    ->label('Ubah')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->modalHeading('Ubah transaksi')
                    ->modalSubmitActionLabel('Simpan')
                    ->form(Transaksi::form())
                    ->fillForm(fn (Transaksi $record): array => $record->attributesToArray())
                    ->hidden(fn (Transaksi $record): bool => ! $this->dapatMengelola($record))
                    ->action(fn (Transaksi $record, array $data): Transaksi => app(TransaksiService::class)->ubah(auth()->user(), $record, $data)),

                DeleteAction::make()
                    ->hidden(fn (Transaksi $record): bool => ! $this->dapatMengelola($record))
                    ->using(fn (Transaksi $record): bool => app(TransaksiService::class)->hapus(auth()->user(), $record)),
            ])
            ->defaultSort('tanggal', 'desc')
            ->paginated([10, 25, 50]);
    }

    private function dapatMengelola(Transaksi $transaksi): bool
    {
        return ! auth()->user()->isAdmin()
            && blank($transaksi->audit_saldo_dompet_detail_id)
            && $transaksi->user_id === auth()->id()
            && auth()->user()->dapatMengelolaTransaksiPada($transaksi->buku_kas)
            && auth()->user()->dapatMengelolaTransaksiPadaDompet($transaksi->dompet);
    }
}
