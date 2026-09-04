<?php

namespace App\Enums;

enum StatusLangganan: string
{
    case MenungguPembayaran = 'menunggu_pembayaran';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'Menunggu pembayaran',
            self::MenungguVerifikasi => 'Menunggu verifikasi',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'warning',
            self::MenungguVerifikasi => 'info',
            self::Disetujui => 'success',
            self::Ditolak => 'danger',
            self::Dibatalkan => 'gray',
        };
    }
}
