<?php

namespace App\Models;

use App\Models\User;
use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use Filament\Forms\Components\Grid;
use App\Observers\TransaksiObserver;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

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

    public static function form()
    {
        return [
            Grid::make()
                ->schema([
                    Select::make('buku_kas_id')
                        ->label('Buku Kas')
                        ->relationship('buku_kas', 'nama_buku')
                        ->required(),

                    Select::make('jenis_transaksi_id')
                        ->label('Kategori')
                        ->relationship(
                            'jenis_transaksi',
                            'nama_jenis',
                            fn($query) => $query->where('tipe', 'Pemasukan')
                        )
                        ->required(),

                    DateTimePicker::make('tanggal')
                        ->required()
                        ->seconds(false)
                        ->native(false)
                        // ->default(now())
                        ->displayFormat('d M Y, H:i')
                        ->maxDate(now()),

                    TextInput::make('nominal')
                        ->required()
                        ->numeric(),

                    TextInput::make('deskripsi')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}
