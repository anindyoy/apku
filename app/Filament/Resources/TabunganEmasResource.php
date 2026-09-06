<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\TabunganEmasResource\Pages\ListTabunganEmas;
use App\Models\Dompet;
use App\Models\TabunganEmas;
use App\Models\Transaksi;
use App\Services\TabunganEmasService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
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
            TextInput::make('nama')->required()->maxLength(100),
            TextInput::make('merek')->maxLength(100),
            TextInput::make('produk')->maxLength(150),
            TextInput::make('kadar')->suffix('%')->numeric()->minValue(0.01)->maxValue(100)->default(99.99)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bukuKas.nama_buku')->label('Kas')->searchable(),
                TextColumn::make('nama')->searchable(),
                TextColumn::make('merek')->placeholder('-'),
                TextColumn::make('produk')->placeholder('-'),
                TextColumn::make('berat_gram')->label('Berat')->suffix(' gram')->numeric(decimalPlaces: 4),
                TextColumn::make('total_modal')->label('Total modal')->money('IDR'),
            ])
            ->actions([
                Action::make('saldoAwal')
                    ->label('Saldo awal')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn (TabunganEmas $record): bool => $record->bukuKas->user_id === auth()->id() && (float) $record->berat_gram === 0.0)
                    ->form([
                        TextInput::make('berat_gram')->label('Berat')->suffix('gram')->numeric()->minValue(0.0001)->required(),
                        TextInput::make('total_modal')->label('Total modal')->prefix('Rp')->numeric()->minValue(0)->default(0)->required(),
                        TextInput::make('catatan')->maxLength(255),
                    ])
                    ->action(fn (TabunganEmas $record, array $data) => app(TabunganEmasService::class)->catatSaldoAwal(
                        auth()->user(), $record, (float) $data['berat_gram'], (int) $data['total_modal'], $data['catatan'] ?? null
                    ))
                    ->successNotificationTitle('Saldo awal emas berhasil dicatat'),
                static::aksiMutasi('beli', 'Beli emas', 'Pengeluaran'),
                static::aksiMutasi('jual', 'Jual emas', 'Pemasukan'),
                Action::make('histori')
                    ->label('Histori')
                    ->icon('heroicon-o-clock')
                    ->modalHeading(fn (TabunganEmas $record): string => 'Histori '.$record->nama)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (TabunganEmas $record) => view('filament.tabungan-emas-histori', [
                        'items' => $record->transaksiEmas()->with('user')->latest('tanggal')->get(),
                    ])),
                EditAction::make()->visible(fn (TabunganEmas $record): bool => $record->bukuKas->user_id === auth()->id()),
                DeleteAction::make()
                    ->visible(fn (TabunganEmas $record): bool => $record->bukuKas->user_id === auth()->id() && ! $record->transaksiEmas()->exists()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()->whereHas('bukuKas', fn (Builder $query): Builder => $query
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

    private static function aksiMutasi(string $jenis, string $label, string $tipeKategori): Action
    {
        return Action::make($jenis)
            ->label($label)
            ->icon($jenis === 'beli' ? 'heroicon-o-shopping-cart' : 'heroicon-o-currency-dollar')
            ->visible(fn (TabunganEmas $record): bool => auth()->user()->dapatMengelolaTransaksiPada($record->bukuKas))
            ->form([
                TextInput::make('berat_gram')->label('Berat')->suffix('gram')->numeric()->minValue(0.0001)->required(),
                TextInput::make('harga_per_gram')->label('Harga per gram')->prefix('Rp')->numeric()->minValue(1)->required(),
                TextInput::make('biaya_tambahan')->label('Biaya tambahan')->prefix('Rp')->numeric()->minValue(0)->default(0)->required(),
                Select::make('dompet_id')->label('Dompet')->options(fn (): array => Transaksi::opsiDompetYangDapatDikelola())->required(),
                Select::make('jenis_transaksi_id')->label('Aktivitas')->options(fn (): array => Transaksi::opsiJenisTransaksi($tipeKategori))->required(),
                DateTimePicker::make('tanggal')->seconds(false)->maxDate(now())->default(now())->required(),
                TextInput::make('catatan')->maxLength(255),
            ])
            ->action(function (TabunganEmas $record, array $data) use ($jenis): void {
                $dompet = Dompet::findOrFail($data['dompet_id']);
                app(TabunganEmasService::class)->{$jenis}(auth()->user(), $record, $dompet, $data);
            })
            ->successNotificationTitle($label.' berhasil dicatat');
    }
}
