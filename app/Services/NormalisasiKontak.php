<?php

namespace App\Services;

// Normalisasi identitas agar kuota sekali per akun/HP/email tidak mudah diakali.
class NormalisasiKontak
{
    public static function hp(?string $hp): ?string
    {
        if (! filled($hp)) {
            return null;
        }

        // Hanya digit yang dipakai; spasi, strip, titik, dan plus dibuang.
        $digit = (string) preg_replace('/\D+/', '', $hp);

        if ($digit === '') {
            return null;
        }

        // Samakan format 08xx, 62xx, dan +62xx menjadi 08xx.
        if (str_starts_with($digit, '62')) {
            $digit = '0'.substr($digit, 2);
        }

        return $digit;
    }

    public static function email(?string $email): ?string
    {
        if (! filled($email)) {
            return null;
        }

        $email = strtolower(trim($email));
        $bagian = explode('@', $email);

        if (count($bagian) !== 2 || $bagian[0] === '' || $bagian[1] === '') {
            return $email;
        }

        [$lokal, $domain] = $bagian;

        // Googlemail adalah alias Gmail.
        if ($domain === 'googlemail.com') {
            $domain = 'gmail.com';
        }

        // Gmail mengabaikan titik dan +alias pada nama lokal.
        if ($domain === 'gmail.com') {
            $lokal = str_replace('.', '', $lokal);
            $lokal = explode('+', $lokal)[0];
        }

        return $lokal.'@'.$domain;
    }
}
