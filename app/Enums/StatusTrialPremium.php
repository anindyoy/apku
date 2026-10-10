<?php

namespace App\Enums;

enum StatusTrialPremium: string
{
    case Aktif = 'aktif';
    case Berakhir = 'berakhir';
    case Dikonversi = 'dikonversi';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Berakhir => 'Berakhir',
            self::Dikonversi => 'Dikonversi',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::Berakhir => 'gray',
            self::Dikonversi => 'info',
        };
    }
}
