<?php

namespace App\Filament\Forms;

use App\Models\Dompet;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rule;

class DompetFormSchema
{
    /** @return array<int, mixed> */
    public static function fields(): array
    {
        return [
            TextInput::make('nama_dompet')
                ->label('Nama dompet')
                ->required()
                ->maxLength(50)
                ->rules(fn (?Dompet $record): array => [
                    Rule::unique('dompet', 'nama_dompet')
                        ->where('user_id', auth()->id())
                        ->ignore($record?->id),
                ]),

            TextInput::make('saldo')
                ->prefix('Rp')
                ->numeric()
                ->default(0)
                ->disabled()
                ->dehydrated(),

            TextInput::make('description')
                ->label('Deskripsi')
                ->maxLength(200),
        ];
    }
}
