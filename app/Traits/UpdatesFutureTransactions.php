<?php

namespace App\Traits;

use App\Models\Transaksi;

trait UpdatesFutureTransactions
{
    public function updateFutureTransactions(
        Transaksi $transaksi,
        string $action = 'create',
        $oldNominal = null
    ) {
        $previousTransaction = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
            ->where('id', '!=', $transaksi->id)
            ->where('tanggal', '<=', $transaksi->tanggal)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        $futureTransactions = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
            ->where('id', '!=', $transaksi->id)
            ->where('tanggal', '>', $transaksi->tanggal)
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();

        if (!empty($futureTransactions) && $action === 'create') {
            $previousTransaction = $transaksi;

            foreach ($futureTransactions as $futureTransaction) {
                $futureTransaction->saldo_awal = $previousTransaction->saldo_akhir;

                if (in_array($futureTransaction->jenis_transaksi, ['Pengeluaran', 'Transfer Pengeluaran'])) {
                    $futureTransaction->saldo_akhir = $futureTransaction->saldo_awal - $futureTransaction->nominal;
                } else {
                    $futureTransaction->saldo_akhir = $futureTransaction->saldo_awal + $futureTransaction->nominal;
                }

                $futureTransaction->save();
                $previousTransaction = $futureTransaction;
            }
        }

        if ($futureTransactions->isNotEmpty() && $action === 'delete') {
            $previousTransaction = $transaksi;

            foreach ($futureTransactions as $futureTransaction) {
                if ($action === 'delete') {
                    $futureTransaction->saldo_awal = $previousTransaction->saldo_awal;
                } else {
                    $futureTransaction->saldo_awal = $previousTransaction->saldo_akhir;
                }

                if (in_array($futureTransaction->jenis_transaksi, ['Pengeluaran', 'Transfer Pengeluaran'])) {
                    $futureTransaction->saldo_akhir = $futureTransaction->saldo_awal - $futureTransaction->nominal;
                } else {
                    $futureTransaction->saldo_akhir = $futureTransaction->saldo_awal + $futureTransaction->nominal;
                }

                $futureTransaction->save();
                $previousTransaction = $futureTransaction;
            }
        }

        $saldo = $previousTransaction ? $previousTransaction->saldo_akhir : 0;

        if ($action === 'update' && $oldNominal !== null) {
            $saldo = in_array($transaksi->jenis_transaksi, ['Pengeluaran', 'Transfer Pengeluaran'])
                ? $saldo + $oldNominal
                : $saldo - $oldNominal;
        }

        if ($futureTransactions->isNotEmpty() || $action === 'delete') {
            $bukuKas = $transaksi->buku_kas;
            $bukuKas->saldo = $saldo;
            $bukuKas->save();
        }
    }
}
