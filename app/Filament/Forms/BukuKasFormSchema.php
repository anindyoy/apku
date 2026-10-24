<?php

namespace App\Filament\Forms;

use App\Models\BukuKas;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BukuKasFormSchema
{
    /** @return array<int, mixed> */
    public static function fields(): array
    {
        return [
            TextInput::make('nama_buku')
                ->required()
                ->rules(fn (?BukuKas $record): array => [
                    Rule::unique('buku_kas', 'nama_buku')
                        ->where('user_id', auth()->id())
                        ->ignore($record?->id),
                ])
                ->maxLength(50)
                ->placeholder('Contoh: Kas Pribadi, Kas Rumah Tangga, Kas Usaha'),

            Radio::make('is_tabungan')
                ->label('Tipe Kas')
                ->boolean('Kas Tabungan', 'Kas Biasa')
                ->default(false)
                ->live()
                ->disabled(fn (): bool => ! auth()->user()?->masaAktifBerlaku())
                ->helperText(fn (): string => auth()->user()?->masaAktifBerlaku()
                    ? 'Kas Tabungan punya target dan tenggat.'
                    : 'Fitur Kas Tabungan hanya untuk pengguna Premium.')
                ->rules([
                    function (?BukuKas $record): \Closure {
                        return function (string $attribute, $value, \Closure $fail) use ($record): void {
                            $user = auth()->user();
                            $isTabungan = (bool) $value;
                            if ($isTabungan && ! $user?->masaAktifBerlaku()) {
                                $fail('Fitur Kas Tabungan hanya untuk pengguna Premium.');
                            }
                            if ($record && (bool) $record->is_tabungan !== $isTabungan && ! $user?->masaAktifBerlaku()) {
                                $fail('Hanya pengguna Premium yang dapat mengubah tipe kas.');
                            }
                        };
                    },
                ]),

            KomponenNominal::terapkan(
                TextInput::make('goal')
                    ->label('Target nominal')
                    ->placeholder('Contoh: 10.000.000')
                    ->nullable()
                    ->minValue(10000)
                    ->disabled(fn (): bool => ! auth()->user()?->masaAktifBerlaku())
                    ->visible(fn (Get $get): bool => (bool) $get('is_tabungan'))
                    ->dehydratedWhenHidden()
                    ->rules([
                        function (?BukuKas $record): \Closure {
                            return function (string $attribute, $value, \Closure $fail) use ($record): void {
                                $user = auth()->user();
                                $valInt = filled($value) ? (int) $value : null;
                                $oldInt = $record?->goal !== null ? (int) $record->goal : null;
                                if ($valInt !== $oldInt && ! $user?->masaAktifBerlaku()) {
                                    $fail('Hanya pengguna Premium yang dapat mengubah target kas tabungan.');
                                }
                            };
                        },
                    ])
            ),

            DatePicker::make('tanggal_goal')
                ->label('Tenggat waktu')
                ->placeholder('Pilih tenggat waktu')
                ->nullable()
                ->disabled(fn (): bool => ! auth()->user()?->masaAktifBerlaku())
                ->visible(fn (Get $get): bool => (bool) $get('is_tabungan'))
                ->dehydratedWhenHidden()
                ->rules([
                    function (?BukuKas $record): \Closure {
                        return function (string $attribute, $value, \Closure $fail) use ($record): void {
                            $user = auth()->user();
                            $valStr = filled($value) ? Carbon::parse($value)->format('Y-m-d') : null;
                            $oldStr = $record?->tanggal_goal ? Carbon::parse($record->tanggal_goal)->format('Y-m-d') : null;
                            if ($valStr !== $oldStr && ! $user?->masaAktifBerlaku()) {
                                $fail('Hanya pengguna Premium yang dapat mengubah tenggat waktu kas tabungan.');
                                return;
                            }
                            if (filled($valStr)) {
                                $tanggal = Carbon::parse($valStr)->startOfDay();
                                $tanggalLama = $record?->tanggal_goal ? Carbon::parse($record->tanggal_goal)->startOfDay() : null;
                                if (($tanggalLama === null || ! $tanggal->equalTo($tanggalLama)) && $tanggal->lessThan(today())) {
                                    $fail('Tenggat waktu harus hari ini atau setelahnya.');
                                }
                            }
                        };
                    },
                ]),

            KomponenNominal::terapkan(
                TextInput::make('saldo')
                    ->required()
                    ->placeholder('Contoh: 500.000')
            ),

            TextInput::make('description')
                ->maxLength(200)
                ->default(null)
                ->placeholder('Catatan singkat tentang buku kas ini (maks. 200 karakter)'),

            Toggle::make('hubungkan_kategori')
                ->label('Pakai semua kategori saya di kas ini')
                ->helperText('Aktifkan jika kas ini ingin memakai seluruh kategori yang sudah ada. Hubungan kategori dapat diubah di Setting > Kategori.')
                ->default(false)
                ->visible(fn (?BukuKas $record, ?string $operation = null): bool => $record === null && $operation !== 'edit'),
        ];
    }
}
