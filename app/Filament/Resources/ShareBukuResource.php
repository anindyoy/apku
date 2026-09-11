<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\ShareBukuResource\Pages;
use App\Models\BukuKas;
use App\Models\ShareBuku;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use UnitEnum;

class ShareBukuResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $model = ShareBuku::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Kolaborator Kas';

    protected static ?string $modelLabel = 'Kolaborator Kas';

    protected static ?string $pluralModelLabel = 'Kolaborator Kas';

    protected static ?string $pluralLabel = 'Kolaborator Kas';

    protected static ?string $slug = 'kolaborator-buku';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('buku_kas_id')
                ->label('Kas')
                ->options(fn (): array => BukuKas::withoutGlobalScopes()
                    ->where('user_id', auth()->id())
                    ->pluck('nama_buku', 'id')->all())
                ->rules([Rule::exists('buku_kas', 'id')->where('user_id', auth()->id())])
                ->searchable()
                ->required(),
            Select::make('user_id')
                ->label('Email Kolaborator')
                ->options(fn (): array => User::query()->notAdmin()
                    ->whereKeyNot(auth()->id())
                    ->whereNotNull('email_verified_at')
                    ->orderBy('email')
                    ->pluck('email', 'id')->all())
                ->searchable()
                ->preload()
                ->disabled(fn (?ShareBuku $record): bool => $record !== null)
                ->dehydrated()
                ->rules([
                    Rule::exists('users', 'id')
                        ->whereNot('id', auth()->id())
                        ->where('role', '!=', 'admin')
                        ->whereNotNull('email_verified_at'),
                ])
                ->required(),
            Select::make('privilege')
                ->label('Hak Akses')
                ->options(['viewer' => 'Viewer', 'editor' => 'Editor'])
                ->required(),
            DateTimePicker::make('berlaku_mulai')
                ->label('Mulai Berlaku')
                ->seconds(false)
                ->default(now())
                ->required(),
            DateTimePicker::make('berlaku_sampai')
                ->label('Berakhir Pada')
                ->seconds(false)
                ->after('berlaku_mulai')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('buku_kas.nama_buku')->label('Kas')->searchable(),
                TextColumn::make('user.name')->label('Kolaborator')
                    ->description(fn (ShareBuku $record): string => $record->user->email),
                TextColumn::make('privilege')->label('Akses')->badge(),
                TextColumn::make('status')->badge()->state(fn (ShareBuku $record): string => match (true) {
                    $record->berlaku_mulai?->isFuture() => 'Terjadwal',
                    $record->berlaku_sampai?->lte(now()) => 'Kedaluwarsa',
                    default => 'Aktif',
                }),
                TextColumn::make('berlaku_mulai')->label('Mulai')->dateTime('d M Y, H:i'),
                TextColumn::make('berlaku_sampai')->label('Berakhir')->dateTime('d M Y, H:i')->placeholder('Tanpa batas'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()->label('Cabut akses')])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()->label('Cabut akses')])]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('buku_kas', fn (Builder $query): Builder => $query
                ->withoutGlobalScopes()
                ->where('user_id', auth()->id()));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShareBukus::route('/'),
        ];
    }
}
