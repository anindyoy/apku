<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use App\Observers\TransaksiObserver;
use App\Services\OpsiSelectCache;
use Database\Factories\TransaksiFactory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([UserScope::class])]
#[ObservedBy([TransaksiObserver::class])]
class Transaksi extends Model
{
    /** @use HasFactory<TransaksiFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $table = 'transaksi';

    protected static function booted(): void
    {
        static::creating(function (Transaksi $transaksi): void {
            if ($transaksi->dompet_id || ! $transaksi->user_id) {
                return;
            }

            $dompet = Dompet::withoutGlobalScopes()->firstOrCreate(
                ['user_id' => $transaksi->user_id, 'nama_dompet' => 'Cash'],
                ['saldo' => 0, 'is_default' => true, 'description' => 'Dompet tunai utama']
            );

            $transaksi->dompet_id = $dompet->id;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buku_kas()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public function dompet()
    {
        return $this->belongsTo(Dompet::class)->withTrashed();
    }

    public function jenis_transaksi()
    {
        return $this->belongsTo(JenisTransaksi::class)->withoutGlobalScopes();
    }

    public function import_transaksi()
    {
        return $this->belongsTo(ImportTransaksi::class);
    }

    public function tujuan_buku_tabungan()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public function asal_buku_tabungan()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public static function form($transfer = false)
    {
        return [
            Select::make('jenis')
                ->options([
                    'Pemasukan' => 'Pemasukan',
                    'Pengeluaran' => 'Pengeluaran',
                    'Transfer Pemasukan' => 'Transfer Pemasukan',
                    'Transfer Pengeluaran' => 'Transfer Pengeluaran',
                ])
                ->disabled()
                ->visible(fn ($record) => $record),

            Select::make('buku_kas_id')
                ->label('Buku Kas')
                ->live()
                ->options(fn (): array => static::opsiBukuKasYangDapatDikelola())
                ->disabled(fn (?Transaksi $record): bool => filled($record?->transfer_code))
                ->required(),

            Select::make('dompet_id')
                ->label($transfer ? 'Dompet Asal' : 'Dompet')
                ->options(fn (): array => static::opsiDompetYangDapatDikelola())
                ->disabled(fn (?Transaksi $record): bool => filled($record?->transfer_code))
                ->required(),

            Select::make('dompet_id_tujuan')
                ->label('Dompet Tujuan')
                ->options(fn (): array => static::opsiDompetYangDapatDikelola())
                ->required()
                ->visible($transfer),

            Select::make('buku_kas_id_tujuan')
                ->label('Buku Kas Tujuan')
                ->options(fn ($get): array => array_filter(
                    static::opsiBukuKasYangDapatDikelola(),
                    fn ($id): bool => (int) $id !== (int) $get('buku_kas_id'),
                    ARRAY_FILTER_USE_KEY,
                ))
                ->required()
                ->visible($transfer),

            Select::make('jenis_transaksi_id')
                ->label('Kategori')
                ->hidden(
                    fn ($record = null) => $transfer || ($record && in_array(
                        $record->jenis,
                        ['Transfer Pemasukan', 'Transfer Pengeluaran']
                    ))
                )
                ->options(fn (?Transaksi $record): array => static::opsiJenisTransaksi($record?->jenis))
                ->required(),

            DateTimePicker::make('tanggal')
                ->required()
                ->seconds(false)
                ->native(false)
                ->closeOnDateSelection()
                ->displayFormat('d M Y, H:i')
                ->maxDate(now()),

            TextInput::make('nominal')
                ->required()
                ->numeric(),

            TextInput::make('deskripsi')
                ->columnSpanFull(),
        ];
    }

    private static function batasiBukuKasYangDapatDikelola($query)
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($query) use ($user): void {
            if ($user->masaAktifBerlaku()) {
                $query->where('buku_kas.user_id', $user->id);
            } else {
                $query->whereKey(array_filter([
                    $user->idBukuKasUtama(),
                    $user->idBukuKasTambahanGratis(),
                ]));
            }

            $query->orWhereHas('shares', fn ($query) => $query
                ->aktif()
                ->where('user_id', $user->id)
                ->where('privilege', 'editor'));
        });
    }

    private static function batasiDompetYangDapatDikelola($query)
    {
        $user = auth()->user();
        $query->withoutTrashed();

        if ($user->isAdmin() || $user->masaAktifBerlaku()) {
            return $query;
        }

        return $query->whereKey(array_filter([
            $user->idDompetUtama(),
            $user->idDompetTambahanGratis(),
        ]));
    }

    public static function opsiDompetYangDapatDikelola(): array
    {
        return OpsiSelectCache::ingat('dompet', fn (): array => static::batasiDompetYangDapatDikelola(Dompet::query())
            ->pluck('nama_dompet', 'id')
            ->all(), auth()->id(), 'dapat-dikelola');
    }

    public static function opsiDompetSumberTransfer(): array
    {
        return OpsiSelectCache::ingat('dompet', fn (): array => Dompet::query()
            ->pluck('nama_dompet', 'id')
            ->all(), auth()->id(), 'aktif');
    }

    public static function opsiBukuKasYangDapatDikelola(): array
    {
        return OpsiSelectCache::ingat('buku-kas', fn (): array => static::batasiBukuKasYangDapatDikelola(BukuKas::query())
            ->pluck('nama_buku', 'id')
            ->all(), auth()->id(), 'dapat-dikelola');
    }

    public static function opsiJenisTransaksi(?string $tipe = null): array
    {
        $tipe = in_array($tipe, ['Pemasukan', 'Pengeluaran'], true) ? $tipe : 'semua';

        if ($tipe === 'semua') {
            return array_replace(
                static::opsiJenisTransaksi('Pemasukan'),
                static::opsiJenisTransaksi('Pengeluaran'),
            );
        }

        return OpsiSelectCache::ingat('jenis-transaksi', fn (): array => JenisTransaksi::query()
            ->where('tipe', $tipe)
            ->orderBy('nama_jenis')
            ->pluck('nama_jenis', 'id')
            ->all(), auth()->id(), $tipe);
    }
}
