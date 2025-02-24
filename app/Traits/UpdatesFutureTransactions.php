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
            // ->when($action !== 'create', function ($query) use ($transaksi) {
            //     return $query->where('id', '!=', $transaksi->id);
            // })
            ->where('id', '!=', $transaksi->id)
            ->where('tanggal', '<=', $transaksi->tanggal)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        $futureTransactions = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
            // ->when($action !== 'create', function ($query) use ($transaksi) {
            //     return $query->where('id', '!=', $transaksi->id);
            // })
            ->where('id', '!=', $transaksi->id)
            ->where('tanggal', '>', $transaksi->tanggal)
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();

        if ($futureTransactions->isEmpty() && $action !== 'delete') {
            $bukuKas = $transaksi->buku_kas;
            $saldo = $previousTransaction ? $previousTransaction->saldo_akhir : 0;

            $saldo = !in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
                ? $saldo + $transaksi->nominal
                : $saldo - $transaksi->nominal;
            $bukuKas->saldo = $saldo;

            $bukuKas->save();
            return;
        }

        $saldo = $previousTransaction ? $previousTransaction->saldo_akhir : 0;

        if ($action === 'update' && $oldNominal !== null) {
            $saldo = in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
                ? $saldo + $oldNominal
                : $saldo - $oldNominal;
        }

        // if ($action !== 'delete') {
        //     $saldo = !in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
        //         ? $saldo + $transaksi->nominal
        //         : $saldo - $transaksi->nominal;

        //     if ($action === 'update') {
        //         $transaksi->saldo_awal = $previousTransaction ? $previousTransaction->saldo_akhir : 0;
        //         $transaksi->saldo_akhir = $saldo;
        //         $transaksi->save();
        //     }
        // }

        dd(
            $futureTransactions,
            $transaksi->tanggal,
            $transaksi->delete(),
            $previousTransaction->saldo_akhir,
            $transaksi->saldo_akhir,
            // $previousTransaction->saldo_akhir,
            // $transaksi->nominal,
            $saldo
            // $action,
        );

        foreach ($futureTransactions as $futureTransaction) {
            $futureTransaction->saldo_awal = $saldo;

            $saldo = !in_array($futureTransaction->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
                ? $saldo + $futureTransaction->nominal
                : $saldo - $futureTransaction->nominal;

            $futureTransaction->saldo_akhir = $saldo;
            $futureTransaction->saveQuietly();
        }

        if ($futureTransactions->isNotEmpty() || $action === 'delete') {
            $bukuKas = $transaksi->buku_kas;
            $bukuKas->saldo = $saldo;
            $bukuKas->save();
        }
    }
}
