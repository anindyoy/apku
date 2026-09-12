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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
            TextInput::make('user_id')
                ->label('Email Kolaborator')
                ->email()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateHydrated(function (TextInput $component, ?ShareBuku $record): void {
                    if ($record !== null) {
                        $component->state($record->user?->email);
                    }
                })
                ->hint(function (?string $state): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    return User::where('email', trim($state))->exists()
                        ? 'Email sudah terdaftar di aplikasi.'
                        : 'Email belum terdaftar di aplikasi.';
                })
                ->hintColor(fn (?string $state): string => filled($state) && User::where('email', trim($state))->exists() ? 'success' : 'danger')
                ->helperText('Kosongkan untuk membuat link publik. Siapa pun yang memiliki link dapat melihat kas tanpa login selama akses berlaku.')
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if (blank($state)) {
                        $set('privilege', 'viewer');
                    }
                })
                ->disabled(fn (?ShareBuku $record): bool => $record !== null)
                ->dehydrated()
                ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                ->dehydrateStateUsing(fn (?string $state, ?ShareBuku $record): ?int => $record !== null
                    ? $record->user_id
                    : (blank($state) ? null : User::where('email', trim($state))->firstOrFail()->id))
                ->rules([
                    Rule::exists('users', 'email')
                        ->whereNot('id', auth()->id())
                        ->whereNot('role', 'admin')
                        ->whereNotNull('email_verified_at'),
                ])
                ->validationMessages([
                    'exists' => 'Email harus terdaftar dan terverifikasi, bukan email sendiri atau admin.',
                ])
                ->nullable(),
            Select::make('privilege')
                ->label('Hak Akses')
                ->options(fn (Get $get): array => blank($get('user_id')) ? ['viewer' => 'Viewer'] : ['viewer' => 'Viewer', 'editor' => 'Editor'])
                ->default('viewer')
                ->dehydrateStateUsing(fn (string $state, Get $get): string => blank($get('user_id')) ? 'viewer' : $state)
                ->required(),
            DatePicker::make('berlaku_mulai')
                ->label('Mulai Berlaku')
                ->default(today())
                ->required(),
            DatePicker::make('berlaku_sampai')
                ->label('Berakhir Pada')
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
                    ->placeholder('Publik — siapa pun dengan link')
                    ->description(fn (ShareBuku $record): ?string => $record->user?->email),
                TextColumn::make('link_publik')->label('Link publik')
                    ->state(fn (ShareBuku $record): ?string => $record->urlPublik())
                    ->url(fn (ShareBuku $record): ?string => $record->urlPublik())
                    ->openUrlInNewTab()
                    ->copyable()
                    ->limit(35)
                    ->placeholder('—'),
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
