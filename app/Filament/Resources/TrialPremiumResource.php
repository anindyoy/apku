<?php

namespace App\Filament\Resources;

use App\Enums\StatusTrialPremium;
use App\Filament\Resources\TrialPremiumResource\Pages\ListTrialPremiums;
use App\Models\TrialPremium;
use App\Services\BatalkanTrialPremium;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

// Daftar baca-saja trial Premium untuk admin (dengan aksi batalkan bila perlu).
class TrialPremiumResource extends Resource
{
    protected static ?string $model = TrialPremium::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?string $navigationLabel = 'Trial Premium';

    protected static ?string $modelLabel = 'Trial Premium';

    protected static ?string $pluralModelLabel = 'Trial Premium';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Pengguna')->searchable()->placeholder('Akun dihapus')->wrap(),
                TextColumn::make('user.email')->label('Email')->searchable()->placeholder('-')->toggleable(),
                TextColumn::make('mulai_pada')->label('Mulai')->date('d M Y')->sortable(),
                TextColumn::make('berakhir_pada')->label('Berakhir')->date('d M Y')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StatusTrialPremium $state): string => $state->label())
                    ->color(fn (StatusTrialPremium $state): string => $state->warna()),
                TextColumn::make('langganan_id')->label('Order konversi')->placeholder('-')->toggleable(),
                TextColumn::make('dikonversi_pada')->label('Dikonversi')->dateTime('d M Y H:i')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(StatusTrialPremium::cases())
                    ->mapWithKeys(fn (StatusTrialPremium $status): array => [$status->value => $status->label()])
                    ->all()),
            ])
            ->actions([
                Action::make('batalkan')
                    ->label('Batalkan')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan trial Premium?')
                    ->modalDescription('Akses Premium dari trial langsung dicabut dan akun kembali Reguler bila belum punya masa aktif berbayar.')
                    ->action(function (TrialPremium $record): void {
                        app(BatalkanTrialPremium::class)->handle(auth()->user(), $record);
                        Notification::make()->title('Trial Premium dibatalkan')->success()->send();
                    })
                    ->visible(fn (TrialPremium $record): bool => auth()->user()->isAdmin()
                        && $record->status === StatusTrialPremium::Aktif),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrialPremiums::route('/'),
        ];
    }
}
