<?php

namespace App\Filament\Pages;

use App\Models\Transaksi;
use BackedEnum;
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
                    'buku_kas:id,nama_buku',
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
            ->searchPlaceholder('Cari deskripsi, kategori, buku kas, tipe, nominal, atau pengguna...')
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
                    ->tooltip(fn (string $state): string => $state)
                    ->icon(fn (string $state): string => match ($state) {
                        'Pemasukan' => 'heroicon-o-arrow-down-on-square',
                        'Pengeluaran' => 'heroicon-o-arrow-up-on-square',
                        default => 'heroicon-o-arrow-path-rounded-square',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Pemasukan' => 'success',
                        'Pengeluaran' => 'danger',
                        default => 'primary',
                    }),

                TextColumn::make('tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('buku_kas.nama_buku')
                    ->label('Buku Kas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('dompet.nama_dompet')
                    ->label('Dompet')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kategori_pencarian')
                    ->label('Kategori')
                    ->state(fn (Transaksi $record): string => match ($record->jenis) {
                        'Transfer Pemasukan' => 'Transfer dari '.($record->asal_buku_tabungan?->nama_buku ?? '-'),
                        'Transfer Pengeluaran' => 'Transfer ke '.($record->tujuan_buku_tabungan?->nama_buku ?? '-'),
                        default => $record->jenis_transaksi?->nama_jenis ?? 'Tanpa kategori',
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query
                                ->whereHas('jenis_transaksi', fn (Builder $query) => $query->where('nama_jenis', 'like', "%{$search}%"))
                                ->orWhereHas('asal_buku_tabungan', fn (Builder $query) => $query->where('nama_buku', 'like', "%{$search}%"))
                                ->orWhereHas('tujuan_buku_tabungan', fn (Builder $query) => $query->where('nama_buku', 'like', "%{$search}%"));
                        });
                    })
                    ->description(fn (Transaksi $record): ?string => $record->deskripsi)
                    ->wrap(),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deskripsi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nominal')
                    ->money('IDR', locale: 'id')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->searchable()
                    ->visible(fn (): bool => auth()->user()->isAdmin()),
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
                    ->label('Buku kas')
                    ->relationship('buku_kas', 'nama_buku')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('dompet_id')
                    ->label('Dompet')
                    ->relationship('dompet', 'nama_dompet')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->paginated([10, 25, 50]);
    }
}
