<?php

namespace App\Models;

use App\Models\User;
use App\Models\Scopes\UserScope;
use App\Models\UtangPiutangDetail;
use Filament\Support\Colors\Color;
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

    public static function tableColumns()
    {
        return [
            // TextColumn::make('id'),

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
                    fn($record) => "Aktivitas terakhir: " . $record->utang_piutang_detail()->latest()->first()->created_at
                ),

            TextColumn::make('kepada'),

            TextColumn::make('deskripsi'),

            TextColumn::make('nominal')
                ->numeric()
                ->prefix('Rp '),
        ];
    }

    public function getNominalAttribute()
    {
        if ($this->tipe == 'piutang') {
            return $this->utang_piutang_detail()->kurang()->sum('nominal') - $this->utang_piutang_detail()->tambah()->sum('nominal');
        } else
            return $this->utang_piutang_detail()->tambah()->sum('nominal') - $this->utang_piutang_detail()->kurang()->sum('nominal');
    }

    public function scopeUtang($query)
    {
        return $query->whereTipe('utang');
    }

    public function scopePiutang($query)
    {
        return $query->whereTipe('piutang');
    }
}
