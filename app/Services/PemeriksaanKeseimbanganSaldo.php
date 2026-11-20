<?php

namespace App\Services;

use App\Models\User;

class PemeriksaanKeseimbanganSaldo
{
    /**
     * Menjumlahkan saldo tersimpan milik pengguna tanpa menghitung ulang dari transaksi.
     * Pencocokan saldo mengikuti rumus: sumber dana = total kas + total utang - total piutang.
     */
    public function ringkasan(User $user): array
    {
        $saldoKas = $user->buku_kas()
            ->when(! $user->hitungTabunganDiTotal(), fn ($query) => $query->where('is_tabungan', false))
            ->sum('saldo');

        $saldoSumberDana = $user->sumberDana()->sum('saldo');
        $totalUtang = $this->totalUtangPiutang($user, 'utang');
        $totalPiutang = $this->totalUtangPiutang($user, 'piutang');

        $harapanSumberDana = (int) $saldoKas + $totalUtang - $totalPiutang;
        $selisih = (int) $saldoSumberDana - $harapanSumberDana;

        return [
            'saldo_kas' => (int) $saldoKas,
            'saldo_sumber_dana' => (int) $saldoSumberDana,
            'total_utang' => $totalUtang,
            'total_piutang' => $totalPiutang,
            'harapan_sumber_dana' => $harapanSumberDana,
            'selisih' => $selisih,
            'seimbang' => $selisih === 0,
            'cakupan_kas' => $user->hitungTabunganDiTotal() ? 'semua kas' : 'kas biasa',
        ];
    }

    /** Menghitung sisa nominal utang atau piutang pengguna dari detail transaksinya. */
    private function totalUtangPiutang(User $user, string $tipe): int
    {
        $result = \Illuminate\Support\Facades\DB::table('utang_piutang_detail')
            ->join('utang_piutang', 'utang_piutang_detail.utang_piutang_id', '=', 'utang_piutang.id')
            ->where('utang_piutang.user_id', $user->id)
            ->where('utang_piutang.tipe', $tipe)
            ->selectRaw('SUM(CASE WHEN utang_piutang_detail.tipe = "tambah" THEN utang_piutang_detail.nominal ELSE -utang_piutang_detail.nominal END) as total')
            ->value('total');

        return (int) ($result ?? 0);
    }
}
