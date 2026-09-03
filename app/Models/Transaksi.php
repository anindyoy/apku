<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use App\Observers\TransaksiObserver;
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
        return $this->belongsTo(JenisTransaksi::class);
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
                ->relationship(
                    'buku_kas',
                    'nama_buku',
                    fn ($query) => static::batasiBukuKasYangDapatDikelola($query)
                )
                ->required(),

            Select::make('dompet_id')
                ->label($transfer ? 'Dompet Asal' : 'Dompet')
                ->relationship(
                    'dompet',
                    'nama_dompet',
                    fn ($query) => static::batasiDompetYangDapatDikelola($query)
                )
                ->required(),

            Select::make('dompet_id_tujuan')
                ->label('Dompet Tujuan')
                ->options(fn (): array => static::opsiDompetYangDapatDikelola())
                ->required()
                ->visible($transfer),

            Select::make('buku_kas_id_tujuan')
                ->label('Buku Kas Tujuan')
                ->relationship(
                    'buku_kas',
                    'nama_buku',
                    fn ($query, $get) => static::batasiBukuKasYangDapatDikelola($query)
                        ->whereNot('id', $get('buku_kas_id'))
                )
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
                ->relationship(
                    'jenis_transaksi',
                    'nama_jenis',
                    fn ($query, $record) => $record ? $query->where('tipe', $record->jenis) : $query
                )
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

        if ($user->isSuper() || $user->masaAktifBerlaku()) {
            return $query;
        }

        return $query->where(function ($query) use ($user) {
            $query->whereKey($user->idBukuKasUtama())
                ->orWhere('id', $user->idBukuKasTambahanGratis());
        });
    }

    private static function batasiDompetYangDapatDikelola($query)
    {
        $user = auth()->user();
        $query->withoutTrashed();

        if ($user->isSuper() || $user->masaAktifBerlaku()) {
            return $query;
        }

        return $query->whereKey(array_filter([
            $user->idDompetUtama(),
            $user->idDompetTambahanGratis(),
        ]));
    }

    public static function opsiDompetYangDapatDikelola(): array
    {
        return static::batasiDompetYangDapatDikelola(Dompet::query())
            ->pluck('nama_dompet', 'id')
            ->all();
    }
}
