<?php

namespace App\Models;

use App\Models\UtangPiutang;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UtangPiutangDetail extends Model
{
    /** @use HasFactory<\Database\Factories\UtangPiutangDetailFactory> */
    use HasFactory;
    protected $guarded = [];
    protected $table = 'utang_piutang_detail';

    public function utang_piutang()
    {
        return $this->belongsTo(UtangPiutang::class);
    }

    public function scopeKurang($query)
    {
        return $query->whereTipe('kurang');
    }

    public function scopeTambah($query)
    {
        return $query->whereTipe('tambah');
    }

    public static function form()
    {
        return [
            TextInput::make('nominal')
                ->required()
                ->numeric(),

            TextInput::make('deskripsi'),

            DateTimePicker::make('created_at')
                ->required()
                ->seconds(false)
                ->native(false)
                ->closeOnDateSelection()
                ->default(now())
                ->displayFormat('d M Y, H:i')
                ->maxDate(now()),
        ];
    }

    public static function action($data, $record, $tipe)
    {
        $data['utang_piutang_id'] = $record;
        $data['tipe'] = $tipe;

        self::create($data);
        $text = $tipe == 'tambah' ? 'Berhasil tambah piutang' : 'Berhasil bayar piutang';

        return Notification::make()
            ->title($text)
            ->success()
            ->send();;
    }
}
