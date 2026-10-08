<?php

namespace App\Filament\Forms;

use App\Models\SumberDana;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rule;

class SumberDanaFormSchema
{
    /** @return array<int, mixed> */
    public static function fields(): array
    {
        return [
            TextInput::make('nama_dompet')
                ->label('Nama sumber dana')
                ->required()
                ->maxLength(50)
                ->rules(fn (?SumberDana $record): array => [
                    Rule::unique('dompet', 'nama_dompet')
                        ->where('user_id', auth()->id())
                        ->ignore($record?->id),
                ])
                ->placeholder('Contoh: Tunai, BCA, Mandiri, OVO, GoPay'),

            TextInput::make('saldo')
                ->prefix('Rp')
                ->numeric()
                ->default(0)
                ->disabled()
                ->dehydrated(),

            TextInput::make('description')
                ->label('Deskripsi')
                ->maxLength(200)
                ->placeholder('Catatan tambahan (maks. 200 karakter)'),
        ];
    }
}
