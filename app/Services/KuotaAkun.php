<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Support\Htmlable;

class KuotaAkun
{
    public static function kas(User $user): Htmlable
    {
        return self::ringkasan($user, 'kas', $user->buku_kas()->count());
    }

    public static function dompet(User $user): Htmlable
    {
        return self::ringkasan($user, 'dompet', $user->dompet()->count());
    }

    private static function ringkasan(User $user, string $jenis, int $jumlah): Htmlable
    {
        $admin = $user->isAdmin();
        $premium = $user->masaAktifBerlaku();
        $tanpaBatas = $admin || $premium;
        $sisa = max(0, 2 - $jumlah);
        $pesan = ucfirst($jenis).' '.$jumlah;

        if ($admin) {
            $pesan .= ', kuota Admin tidak terbatas.';
        } elseif ($premium) {
            $pesan .= ', kuota tidak terbatas hingga '.$user->masa_aktif->format('d/m/Y').'.';
        } else {
            $pesan = match ($sisa) {
                0 => 'Sebagai pengguna reguler, Anda tidak bisa menambah '.$jenis.' lagi karena kuota sudah terpenuhi.',
                1 => 'Sebagai pengguna reguler, Anda hanya bisa menambah satu lagi '.$jenis.'.',
                default => 'Sebagai pengguna reguler, Anda bisa menambah hingga dua '.$jenis.'.',
            };
        }

        return view('filament.components.kuota-akun', [
            'pesan' => $pesan,
            'status' => $tanpaBatas || $sisa > 1 ? 'normal' : ($sisa === 0 ? 'limit' : 'warning'),
        ]);
    }
}
