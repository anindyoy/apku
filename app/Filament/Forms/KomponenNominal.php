<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class KomponenNominal
{
    /**
     * Konfigurasi TextInput agar memiliki separator ribuan saat ditulis dan ditampilkan di UI,
     * tanpa menampilkan digit desimal di belakang koma.
     */
    public static function terapkan(TextInput $input): TextInput
    {
        return $input
            ->numeric(false)
            ->rule('numeric')
            ->prefix('Rp')
            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
            ->stripCharacters('.')
            ->extraInputAttributes(['inputmode' => 'numeric'], merge: true)
            ->formatStateUsing(fn (mixed $state): ?string => static::formatKeUi($state))
            ->dehydrateStateUsing(fn (mixed $state): ?int => static::bersihkanKeInteger($state));
    }

    /**
     * Buat TextInput nominal baru dengan konfigurasi standar.
     */
    public static function make(string $name = 'nominal'): TextInput
    {
        return static::terapkan(
            TextInput::make($name)
        );
    }

    /**
     * Format nilai ke string UI dengan separator ribuan titik tanpa desimal.
     */
    public static function formatKeUi(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $nilai = static::bersihkanKeInteger($state);

        if ($nilai === null) {
            return null;
        }

        return number_format($nilai, 0, ',', '.');
    }

    /**
     * Bersihkan nilai ke integer murni dari format UI atau string numerik.
     */
    public static function bersihkanKeInteger(mixed $state): ?int
    {
        if (blank($state)) {
            return null;
        }

        if (is_int($state)) {
            return $state;
        }

        $str = trim((string) $state);
        $isNegatif = str_starts_with($str, '-');

        // Jika string berupa desimal database standar (misal '100000.00'), potong sebelum titik
        if (str_contains($str, '.') && preg_match('/^-?\d+\.\d{1,2}$/', $str)) {
            $str = explode('.', $str)[0];
        } else {
            // Hapus separator ribuan titik
            $str = str_replace('.', '', $str);
        }

        $digits = preg_replace('/[^\d]/', '', $str);

        if ($digits === '') {
            return null;
        }

        $int = (int) $digits;

        return $isNegatif ? -$int : $int;
    }
}
