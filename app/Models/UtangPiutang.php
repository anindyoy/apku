<?php

namespace App\Models;

use App\Models\User;
use App\Models\Scopes\UserScope;
use App\Models\UtangPiutangDetail;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Widgets\StatsOverviewWidget\Stat;
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

    public static function tableActions()
    {
        return [
            Action::make('Detail')
                ->tooltip('Lihat detail')
                ->hiddenLabel()
                ->icon('heroicon-o-magnifying-glass')
                ->url(
                    fn(?Model $record): string => $record->tipe == 'utang'
                        ? url('/admin/utangs/' . $record->id . '/detail')
                        : url('/admin/piutangs/' . $record->id . '/detail')
                ),

            DeleteAction::make()
                ->tooltip('Hapus')
                ->hiddenLabel()
        ];
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
                    fn($record) => "Aktivitas terakhir: " . date(
                        'd M Y, H:i',
                        strtotime($record->last_activity_date)
                    )
                ),

            TextColumn::make('kepada')
                ->description(
                    fn($record) => $record->tempo ? ('Jatuh tempo: ' . date('d M Y', strtotime($record->tempo))) : null
                )
                ->searchable(),

            TextColumn::make('deskripsi')->wrap(),

            TextColumn::make('nominal')
                ->numeric()
                ->prefix('Rp '),
        ];
    }

    public static function stat($data)
    {
        return [
            Stat::make(
                'Total ' . ucfirst($data->first()?->tipe),
                'Rp ' . number_format($data->sum('nominal'))
            ),
        ];
    }

    public static function formSchema()
    {
        return [
            TextInput::make('kepada')->required(),
            TextInput::make('nominal')->required(),

            DateTimePicker::make('created_at')
                ->default(now())
                ->maxDate(now())
                ->label('Tanggal')
                ->native(false),

            Toggle::make('jatuh_tempo')
                ->dehydrated(false)
                ->live(),

            DateTimePicker::make('tempo')
                ->minDate(now()->addDay())
                ->required()
                ->native(false)
                ->visible(fn($get) => $get('jatuh_tempo')),

            Textarea::make('deskripsi'),
        ];
    }

    public static function headerActions($tipe)
    {
        return [
            CreateAction::make()
                ->hidden(auth()->user()->isSuper())
                ->mutateFormDataUsing(function (array $data) use ($tipe): array {
                    $data['user_id'] = auth()->id();
                    $data['tipe'] = $tipe;
                    unset($data['nominal']);

                    return $data;
                })
                ->after(function ($record, $livewire) {
                    $data = $livewire->mountedActionsData[0];
                    UtangPiutangDetail::create([
                        'utang_piutang_id' => $record->id,
                        'nominal' => $data['nominal'],
                        'tipe' => 'tambah',
                        'deskripsi' => $data['deskripsi'] ?? '',
                        'created_at' => $record->created_at,
                    ]);
                })
                ->modalWidth(MaxWidth::Small),
        ];
    }

    /*
    SCOPES
    */
    public function scopeUtang($query)
    {
        return $query->whereTipe('utang');
    }

    public function scopePiutang($query)
    {
        return $query->whereTipe('piutang');
    }

    public function scopeSelectRawNominalAndLastAcitivityDate($query)
    {
        return $query->select(
            '*',

            DB::raw('(
            SELECT
                   SUM(CASE WHEN tipe = "tambah" THEN nominal ELSE 0 END) -
                   SUM(CASE WHEN tipe = "kurang" THEN nominal ELSE 0 END)
            FROM utang_piutang_detail
            WHERE utang_piutang_id = utang_piutang.id
        ) as nominal'),

            DB::raw('(
            SELECT created_at
            FROM utang_piutang_detail
            WHERE utang_piutang_id = utang_piutang.id
            ORDER BY created_at DESC
            LIMIT 1
        ) as last_activity_date')
        )
            ->orderby('last_activity_date', 'desc');
    }
}
