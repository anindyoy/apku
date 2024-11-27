<?php

namespace App\Models;

use App\Models\User;
use App\Models\Scopes\UserScope;
use App\Models\UtangPiutangDetail;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ScopedBy([UserScope::class])]
class UtangPiutang extends Model
{
    /** @use HasFactory<\Database\Factories\UtangPiutangFactory> */
    use HasFactory;
    protected $guarded = [];
    protected $table = 'utang_piutang';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function utang_piutang_detail()
    {
        return $this->hasMany(UtangPiutangDetail::class);
    }

    public function scopeSelectRawNominalAndLastAcitivityDate($query)
    {
        return $query->select(
            '*',

            DB::raw('(
            SELECT SUM(CASE WHEN tipe = "kurang" THEN nominal ELSE 0 END) -
                   SUM(CASE WHEN tipe = "tambah" THEN nominal ELSE 0 END)
            FROM utang_piutang_detail
            WHERE utang_piutang_id = utang_piutang.id
        ) as nominal'),

            DB::raw('(
            SELECT created_at
            FROM utang_piutang_detail
            WHERE utang_piutang_id = utang_piutang.id
            ORDER BY created_at DESC
            LIMIT 1
        ) as last_activity_date') // Alias for clarity

        );
    }

    public static function tableColumns()
    {
        return [
            TextColumn::make('id'),

            IconColumn::make('status')
                ->tooltip(fn($record) => $record->nominal <= 0 ? 'Selesai' : 'Belum selesai')
                ->getStateUsing(fn($record) => $record->nominal <= 0 ? 'selesai' : 'belum')
                ->icon(fn(string $state): string => match ($state) {
                    'selesai' => 'heroicon-o-check-circle',
                    'belum' => 'heroicon-o-x-circle',
                })
                ->color(fn(string $state): string => match ($state) {
                    'selesai' => 'success',
                    'belum' => 'warning',
                }),

            TextColumn::make('created_at')
                ->date('d M Y')
                ->label('Tanggal')
                ->description(
                    fn($record) => "Aktivitas terakhir: " . date(
                        'd M Y, H:i',
                        strtotime($record->last_activity_date)
                    )
                ),

            TextColumn::make('kepada'),

            TextColumn::make('deskripsi'),

            TextColumn::make('nominal')
                ->numeric()
                ->prefix('Rp '),
        ];
    }

    // public function getNominalAttribute()
    // {
    //     $kurangSum = $this->utang_piutang_detail()->kurang()->sum('nominal');
    //     $tambahSum = $this->utang_piutang_detail()->tambah()->sum('nominal');

    //     return $this->tipe === 'piutang' ? $kurangSum - $tambahSum : $tambahSum - $kurangSum;
    // }


    public function scopeUtang($query)
    {
        return $query->whereTipe('utang');
    }

    public function scopePiutang($query)
    {
        return $query->whereTipe('piutang');
    }
}
