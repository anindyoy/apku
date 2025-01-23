<?php

namespace App\Traits;

use App\Models\Transaksi;

trait UpdatesFutureTransactions
{
    public function updateFutureTransactions(Transaksi $transaksi)
    {
        $futureTransactions = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
            ->where('tanggal', '>', $transaksi->tanggal)
            ->orderBy('tanggal')
            ->get();

        $saldo = $transaksi->saldo_akhir;

        foreach ($futureTransactions as $futureTransaction) {
            $futureTransaction->saldo_awal = $saldo;
            $saldo = !in_array($futureTransaction->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
                ? $saldo + $futureTransaction->nominal
                : $saldo - $futureTransaction->nominal;
            $futureTransaction->saldo_akhir = $saldo;
            $futureTransaction->save();
        }

        // Perbarui saldo buku kas dengan saldo transaksi terakhir
        if ($futureTransactions->isNotEmpty()) {
            $transaksi->buku_kas->saldo = $saldo;
            $transaksi->buku_kas->save();
        }
    }
}
