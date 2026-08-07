<?php

namespace App\Models;

use App\Models\User;
use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use App\Models\Scopes\UserScope;
use App\Observers\TransaksiObserver;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ScopedBy([UserScope::class])]
#[ObservedBy([TransaksiObserver::class])]
class Transaksi extends Model
{
    /** @use HasFactory<\Database\Factories\TransaksiFactory> */
    use HasFactory;
    protected $guarded = [];
    protected $table = 'transaksi';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buku_kas()
    {
        return $this->belongsTo(BukuKas::class);
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
                ->visible(fn($record) => $record),

            // Section wrapper removed for Filament v5 compatibility
            // Using direct schema without wrapper
            [
                    Select::make('buku_kas_id')
                        ->label('Buku Kas')
                        ->live()
                        ->relationship('buku_kas', 'nama_buku')
                        ->required(),

                    Select::make('buku_kas_id_tujuan')
                        ->label('Buku Kas Tujuan')
                        ->relationship('buku_kas', 'nama_buku', fn($query, $get) => $query->whereNot('id', $get('buku_kas_id')))
                        ->required()
                        ->visible($transfer),

                    Select::make('jenis_transaksi_id')
                        ->label('Kategori')
                        ->hidden(
                            fn($record = null) => $transfer || in_array(
                                $record->jenis,
                                ['Transfer Pemasukan', 'Transfer Pengeluaran']
                            )
                        )
                        ->relationship(
                            'jenis_transaksi',
                            'nama_jenis',
                            fn($query, $record) => $query->where('tipe', $record->jenis)
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
            ],
        ];
    }
}
