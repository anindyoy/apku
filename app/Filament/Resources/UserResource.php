<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Pengaturan;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $cluster = Pengaturan::class;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Nama lengkap'),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('contoh@email.com'),
                DateTimePicker::make('email_verified_at'),
                DateTimePicker::make('masa_aktif'),
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Minimal 8 karakter'),
                TextInput::make('type')
                    ->required()
                    ->formatStateUsing(fn ($state) => filled($state) ? ucfirst($state) : null)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? strtolower($state) : null)
                    ->placeholder('regular / premium / admin'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->description(fn ($record) => 'Email: '.$record->email)
                    ->searchable(['name', 'email']),

                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Belum verifikasi'),

                TextColumn::make('type')
                    ->formatStateUsing(fn ($state) => filled($state) ? ucfirst($state) : null)
                    ->description(fn ($record) => $record->masa_aktif ? ('Masa aktif: '.$record->masa_aktif) : null),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label('Status verifikasi')
                    ->nullable()
                    ->placeholder('Semua')
                    ->trueLabel('Terverifikasi')
                    ->falseLabel('Belum terverifikasi'),
            ])
            ->actions([
                // Aksi bagi admin untuk memverifikasi akun pengguna secara manual.
                Action::make('verifikasiEmail')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail())
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi email pengguna')
                    ->modalDescription(fn (User $record): string => "Tandai email {$record->getEmailForVerification()} sebagai terverifikasi?")
                    ->modalSubmitActionLabel('Verifikasi')
                    ->action(function (User $record): void {
                        $record->markEmailAsVerified();

                        Notification::make()
                            ->title('Email pengguna berhasil diverifikasi')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('verifikasiEmail')
                        ->label('Verifikasi email terpilih')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Verifikasi email pengguna terpilih')
                        ->modalDescription('Tandai semua email pengguna yang dipilih sebagai terverifikasi?')
                        ->modalSubmitActionLabel('Verifikasi')
                        ->action(function (Collection $records): void {
                            $records->each(fn (User $user) => $user->hasVerifiedEmail() || $user->markEmailAsVerified());

                            Notification::make()
                                ->title('Email pengguna terpilih berhasil diverifikasi')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
