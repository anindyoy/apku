<?php

namespace App\Filament\Forms;

use App\Models\BukuKas;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                ->maxLength(50),

            TextInput::make('saldo')
                ->prefix('Rp')
                ->required()
                ->numeric(),

            TextInput::make('description')
                ->maxLength(200)
                ->default(null),

            Toggle::make('hubungkan_kategori')
                ->label('Pakai semua kategori saya di kas ini')
                ->helperText('Matikan jika kas ini memerlukan daftar kategori sendiri, misalnya untuk kas bersama. Hubungan kategori dapat diubah di Setting > Kategori.')
                ->default(true)
                ->visible(fn (string $operation): bool => $operation === 'create'),
        ];
    }
}
