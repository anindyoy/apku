<?php

namespace App\Enums;

enum JenisSumberDana: string
{
    case Tunai = 'tunai';
    case Rekening = 'rekening';
    case EWallet = 'e_wallet';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::Rekening => 'Rekening',
            self::EWallet => 'E-wallet',
            self::Lainnya => 'Lainnya',
        };
    }

    public function mendukungHitungUang(): bool
    {
        return $this === self::Tunai;
    }

    public static function opsi(): array
    {
        return [
            self::Tunai->value => self::Tunai->label(),
            self::Rekening->value => self::Rekening->label(),
            self::EWallet->value => self::EWallet->label(),
            self::Lainnya->value => self::Lainnya->label(),
        ];
    }
}
