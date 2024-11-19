<?php

namespace App\Models;

use App\Models\User;
use App\Models\Transaksi;
use App\Models\Scopes\UserScope;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ScopedBy([UserScope::class])]
class JenisTransaksi extends Model
{
    /** @use HasFactory<\Database\Factories\JenisCatatanFactory> */
    use HasFactory;
    protected $table = 'jenis_transaksi';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public static function form()
    {
        return [
            TextInput::make('nama_jenis')
                ->required()
                ->maxLength(255),
        ];
    }

    public static function columns()
    {
        return [
            TextColumn::make('nama_jenis'),
            TextColumn::make('transaksi_count')
                ->counts('transaksi')
                ->label('Jumlah transaksi'),
        ];
    }

    public static function headerActions($type)
    {
        return [
            CreateAction::make()
                ->model(self::class)
                ->mutateFormDataUsing(function (array $data) use ($type): array {
                    $data['user_id'] = auth()->id();
                    $data['tipe'] = $type;
                    return $data;
                })
                ->modalWidth(MaxWidth::Small)
                ->color($type == 'Pengeluaran' ? 'danger' : 'success')
                ->form(self::form())
        ];
    }

    public static function actions($type)
    {
        $color = $type == 'Peneluaran' ? 'danger' : 'success';

        return [
            EditAction::make()
                ->color($color)
                ->modalWidth(MaxWidth::Small)
                ->form(self::form()),

            DeleteAction::make()
                ->visible(fn($record) => !$record->transaksi_count),

            Action::make('Hapus')
                ->modalWidth(MaxWidth::Small)
                ->form(function ($record) use ($type) {
                    return [
                        Select::make('kategori')
                            ->required()
                            ->options(
                                self::orderby('nama_jenis')
                                    ->whereTipe($type)
                                    ->whereNot('id', $record->id)
                                    ->pluck('nama_jenis', 'id')
                            ),
                    ];
                })
                ->modalHeading(fn($record) => 'Hapus ' . $record->nama_jenis)
                ->modalSubheading(
                    'Kategori ini memiliki data transaksi, pilih kategori lain untuk memindahkan kategori penggantinya.'
                )
                ->action(function ($data, $record) {
                    DB::transaction(function () use ($data, $record) {
                        // dd(
                        //     $data['kategori']
                        //     // Transaksi::whereJenisTransaksiId($record->id)->get()
                        // );
                        Transaksi::whereJenisTransaksiId($record->id)
                            ->update(['jenis_transaksi_id' => $data['kategori']]);

                        $record->delete();
                    });
                })
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->visible(fn($record) => $record->transaksi_count),
        ];
    }
}
