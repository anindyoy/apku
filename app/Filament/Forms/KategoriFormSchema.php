<?php

namespace App\Filament\Forms;

use App\Models\Kategori;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;

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

        $semuaKas = Checkbox::make('aktifkan_di_semua_kas')
            ->label('Aktifkan di semua kas')
            ->dehydrated(false)
            ->live()
            ->default(function () use ($kasOptions, $kasDefault): bool {
                $opsi = (array) ($kasOptions() ?? []);
                if (empty($opsi)) {
                    return false;
                }

                $defaultKas = $kasDefault !== null ? (array) $kasDefault() : [];

                return count($defaultKas) > 0 && count(array_intersect(
                    array_map('strval', array_keys($opsi)),
                    array_map('strval', $defaultKas),
                )) === count($opsi);
            })
            ->afterStateUpdated(function (mixed $state, Set $set) use ($kasOptions): void {
                $opsi = (array) ($kasOptions() ?? []);
                $set('kas', (bool) $state ? array_keys($opsi) : []);
            })
            ->columnSpanFull();

        $kas = CheckboxList::make('kas')
            ->label('Dipakai di kas')
            ->options($kasOptions)
            ->helperText('Kategori hanya dapat dipilih pada transaksi kas yang dicentang. Semua kas harus milik pemilik yang sama.')
            ->required($kasRequired)
            ->live()
            ->columns(fn (CheckboxList $component): array|int => count($component->getOptions()) > 5 ? ['default' => 1, 'sm' => 2] : 1)
            ->afterStateUpdated(function (?array $state, Set $set) use ($kasOptions): void {
                $opsi = (array) ($kasOptions() ?? []);
                $opsiKeys = array_map('strval', array_keys($opsi));
                $terpilih = array_map('strval', (array) ($state ?? []));

                $semuaTerpilih = ! empty($opsiKeys) && count(array_intersect($opsiKeys, $terpilih)) === count($opsiKeys);

                $set('aktifkan_di_semua_kas', $semuaTerpilih);
            })
            ->columnSpanFull();

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
            $semuaKas,
            $kas,
        ];
    }
}

