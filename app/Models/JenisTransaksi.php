<?php

namespace App\Models;

use App\Models\Concerns\MembersihkanCacheOpsiSelect;
use App\Models\Scopes\UserScope;
use App\Services\OpsiSelectCache;
use Database\Factories\JenisTransaksiFactory;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
// use Filament\Support\Enums\MaxWidth; // Removed for Filament v5
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

#[ScopedBy([UserScope::class])]
class JenisTransaksi extends Model
{
    /** @use HasFactory<JenisTransaksiFactory> */
    use HasFactory, MembersihkanCacheOpsiSelect;

    protected $table = 'jenis_transaksi';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public static function form(string $type)
    {
        return [
            TextInput::make('nama_jenis')
                ->required()
                ->rules(fn (?self $record): array => [
                    Rule::unique('jenis_transaksi', 'nama_jenis')
                        ->where('user_id', auth()->id())
                        ->where('tipe', $type)
                        ->ignore($record?->id),
                ])
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
                ->hidden(auth()->user()->isAdmin())
                ->model(self::class)
                ->mutateFormDataUsing(function (array $data) use ($type): array {
                    $data['user_id'] = auth()->id();
                    $data['tipe'] = $type;

                    return $data;
                })
                ->modalWidth('small') // Filament v5 uses string
                ->color($type == 'Pengeluaran' ? 'danger' : 'success')
                ->form(self::form($type)),
        ];
    }

    public static function actions($type)
    {
        return [
            EditAction::make()
                ->hidden(fn ($record) => auth()->user()->isAdmin() || $record->is_system)
                ->modalWidth('small') // Filament v5 uses string
                ->form(self::form($type)),

            DeleteAction::make()
                ->visible(fn ($record) => ! $record->is_system && ! $record->transaksi_count && ! auth()->user()->isAdmin()),

            Action::make('Hapus')
                ->hidden(auth()->user()->isAdmin())
                ->modalWidth('small') // Filament v5 uses string
                ->form(function ($record) use ($type) {
                    return [
                        Select::make('kategori')
                            ->required()
                            ->options(fn (): array => array_filter(
                                OpsiSelectCache::ingat('jenis-transaksi', fn (): array => self::orderby('nama_jenis')
                                    ->whereTipe($type)
                                    ->pluck('nama_jenis', 'id')
                                    ->all(), auth()->id(), $type),
                                fn ($id): bool => (int) $id !== (int) $record->id,
                                ARRAY_FILTER_USE_KEY,
                            )),
                    ];
                })
                ->modalHeading(fn ($record) => 'Hapus '.$record->nama_jenis)
                ->modalSubheading(
                    'Kategori ini memiliki data transaksi, pilih kategori lain untuk memindahkan kategori penggantinya.'
                )
                ->action(function ($data, $record) {
                    DB::transaction(function () use ($data, $record) {
                        Transaksi::whereJenisTransaksiId($record->id)
                            ->update(['jenis_transaksi_id' => $data['kategori']]);

                        $record->delete();
                    });
                })
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->visible(fn ($record) => ! $record->is_system && $record->transaksi_count),
        ];
    }

    protected function cacheOpsiSelectEntitas(): string
    {
        return 'jenis-transaksi';
    }
}
