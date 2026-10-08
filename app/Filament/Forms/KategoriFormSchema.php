<?php

namespace App\Filament\Forms;

use App\Models\Kategori;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class KategoriFormSchema
{
    /** @return array<int, mixed> */
    public static function fields(
        Closure $kasOptions,
        bool $kasRequired = true,
        ?Closure $kasDefault = null,
        ?Closure $tipeDefault = null,
    ): array {
        $tipe = Select::make('tipe')
            ->options(array_combine(Kategori::TIPE, Kategori::TIPE))
            ->helperText('Semua berarti kategori ini muncul untuk pemasukan maupun pengeluaran.')
            ->required();

        if ($tipeDefault !== null) {
            $tipe->default($tipeDefault);
        }

        $kas = CheckboxList::make('kas')
            ->label('Dipakai di kas')
            ->options($kasOptions)
            ->helperText('Kategori hanya dapat dipilih pada transaksi kas yang dicentang. Semua kas harus milik pemilik yang sama.')
            ->required($kasRequired);

        if ($kasDefault !== null) {
            $kas->default($kasDefault);
        }

        return [
            TextInput::make('nama')
                ->label('Nama kategori')
                ->required()
                ->maxLength(255)
                ->placeholder('Contoh: Gaji, Makan, Transport, Belanja, Tagihan'),
            $tipe,
            $kas,
        ];
    }
}
