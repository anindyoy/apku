<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\TabunganEmasResource\Pages\ListTabunganEmas;
use App\Models\TabunganEmas;
use App\Services\TabunganEmasService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TabunganEmasResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $model = TabunganEmas::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Tabungan Emas';

    protected static ?string $pluralModelLabel = 'Tabungan Emas';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('buku_kas_id')
                ->label('Kas')
                ->options(fn (): array => auth()->user()->buku_kas()->pluck('nama_buku', 'id')->all())
                ->disabled(fn (?TabunganEmas $record): bool => filled($record))
                ->required(),
            static::inputBerat()->disabled(fn (?TabunganEmas $record): bool => filled($record)),
            TextInput::make('label')->label('Label emas')->placeholder('Emas Antam')->required()->maxLength(100),
            TextInput::make('harga_beli')->label('Harga beli')->prefix('Rp')->placeholder('Contoh: 1500000')->numeric()->integer()->minValue(0)->nullable(),
            DateTimePicker::make('created_at')->label('Dibeli pada')->seconds(false)->default(now())->maxDate(now())->required(),
            Textarea::make('keterangan')->placeholder('Contoh: Disimpan di brankas')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label('Label emas')->searchable(),
                TextColumn::make('berat_gram')->label('Berat')->suffix(' gram')->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 4, ',', '.'), '0'), ',')),
                TextColumn::make('harga_beli')->label('Harga beli')->money('IDR')->placeholder('-'),
                TextColumn::make('keterangan')->placeholder('-')->wrap(),
                TextColumn::make('created_at')->label('Dibeli pada')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultGroup(Group::make('buku_kas_id')->label('Kas')
                ->getTitleFromRecordUsing(fn (TabunganEmas $record): string => $record->bukuKas->nama_buku)
                ->getDescriptionFromRecordUsing(fn (TabunganEmas $record): string => 'Total berat: '.rtrim(rtrim(number_format((float) $record->bukuKas->tabungan_emas_sum_berat_gram, 4, ',', '.'), '0'), ',').' gram'))
            ->actions([
                Action::make('saldoAwal')
                    ->label('Saldo awal')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn (TabunganEmas $record): bool => $record->bukuKas->user_id === auth()->id() && (float) $record->berat_gram === 0.0)
                    ->form([
                        static::inputBerat(),
                        TextInput::make('total_modal')->label('Total modal')->prefix('Rp')->numeric()->minValue(0)->default(0)->required(),
                        TextInput::make('catatan')->maxLength(255),
                    ])
                    ->action(fn (TabunganEmas $record, array $data) => app(TabunganEmasService::class)->catatSaldoAwal(
                        auth()->user(), $record, (float) $data['berat_gram'], (int) $data['total_modal'], $data['catatan'] ?? null
                    ))
                    ->successNotificationTitle('Saldo awal emas berhasil dicatat'),
                EditAction::make()->visible(fn (TabunganEmas $record): bool => $record->bukuKas->user_id === auth()->id()),
                DeleteAction::make()
                    ->visible(fn (TabunganEmas $record): bool => $record->bukuKas->user_id === auth()->id() && ! $record->transaksiEmas()->exists()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['bukuKas' => fn ($query) => $query->withSum('tabunganEmas', 'berat_gram')])
            ->whereHas('bukuKas', fn (Builder $query): Builder => $query
                ->withoutGlobalScopes()
                ->where(function (Builder $query) use ($user): void {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('shares', fn (Builder $query): Builder => $query->aktif()->where('user_id', $user->id));
                }));
    }

    public static function getPages(): array
    {
        return ['index' => ListTabunganEmas::route('/')];
    }

    private static function inputBerat(): TextInput
    {
        return TextInput::make('berat_gram')
            ->label('Berat')->suffix('gram')->placeholder('Contoh: 0,5')
            ->inputMode('decimal')
            ->rules(['numeric', 'min:0.0001', 'max:99999999.9999', 'regex:/^\d+(?:[.,]\d{1,4})?$/'])
            ->mutateStateForValidationUsing(fn ($state) => str_replace(',', '.', (string) $state))
            ->dehydrateStateUsing(fn ($state) => str_replace(',', '.', (string) $state))
            ->required();
    }
}
