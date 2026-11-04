<?php

namespace App\Services;

use App\Models\User;

class PemeriksaanKeseimbanganSaldo
{
    /** Menjumlahkan saldo tersimpan milik pengguna tanpa menghitung ulang dari transaksi. */
    public function ringkasan(User $user): array
    {
        $saldoKas = $user->buku_kas()
            ->when(! $user->hitungTabunganDiTotal(), fn ($query) => $query->where('is_tabungan', false))
            ->sum('saldo');
        $saldoSumberDana = $user->sumberDana()->sum('saldo');
        $selisih = (int) $saldoKas - (int) $saldoSumberDana;

        return [
            'saldo_kas' => (int) $saldoKas,
            'saldo_sumber_dana' => (int) $saldoSumberDana,
            'selisih' => $selisih,
            'seimbang' => $selisih === 0,
            'cakupan_kas' => $user->hitungTabunganDiTotal() ? 'semua kas' : 'kas biasa',
        ];
    }
}
