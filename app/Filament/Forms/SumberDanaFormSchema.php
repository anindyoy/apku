<?php

namespace App\Filament\Forms;

use App\Enums\JenisSumberDana;
use App\Models\SumberDana;
use Filament\Forms\Components\Select;
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
                ->helperText('Tempat uang Anda disimpan, misalnya uang tunai, rekening bank, atau saldo e-wallet.')
                ->required()
                ->maxLength(50)
                ->rules(fn (?SumberDana $record): array => [
                    Rule::unique('sumber_dana', 'nama_dompet')
                        ->where('user_id', auth()->id())
                        ->whereNull('deleted_at')
                        ->ignore($record?->id),
                ])
                // Komentar kode boleh menyebut nama kolom; placeholder UI memakai sumber dana.
                ->placeholder('Contoh: Tunai di rumah, BCA, GoPay'),

            Select::make('jenis')
                ->label('Jenis sumber dana')
                ->options([
                    JenisSumberDana::Tunai->value => 'Tunai',
                    JenisSumberDana::Rekening->value => 'Rekening bank',
                    JenisSumberDana::EWallet->value => 'E-wallet',
                    JenisSumberDana::Lainnya->value => 'Lainnya',
                ])
                ->default(JenisSumberDana::Tunai->value)
                ->required(),

            KomponenNominal::terapkan(
                TextInput::make('saldo')
                    ->label('Saldo')
                    ->default(0)
                    ->required()
                    ->numeric()
            ),

            TextInput::make('description')
                ->label('Deskripsi')
                ->maxLength(200)
                ->placeholder('Catatan tambahan (maks. 200 karakter)'),
        ];
    }
}
