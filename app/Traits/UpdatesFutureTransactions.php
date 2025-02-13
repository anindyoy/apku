<?php

namespace App\Traits;

use App\Models\Transaksi;

trait UpdatesFutureTransactions
{
    // public function updateFutureTransactions(
    //     Transaksi $transaksi,
    //     string $action = 'create',
    //     $oldNominal = null
    // ) {        // Get the previous transaction to determine the starting balance
    //     $previousTransaction = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
    //         ->when($action !== 'create', function ($query) use ($transaksi) {
    //             return $query->where('id', '!=', $transaksi->id);
    //         })
    //         ->where('tanggal', '<=', $transaksi->tanggal)
    //         ->orderBy('tanggal', 'desc')
    //         ->orderBy('created_at', 'desc')
    //         ->first();

    //     // Get future transactions that need to be updated
    //     $futureTransactions = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
    //         ->when($action !== 'create', function ($query) use ($transaksi) {
    //             return $query->where('id', '!=', $transaksi->id);
    //         })
    //         ->where('tanggal', '>', $transaksi->tanggal)
    //         ->orderBy('tanggal')
    //         ->orderBy('created_at')
    //         ->get();

    //     // Set initial balance from previous transaction or 0 if none exists
    //     $saldo = $previousTransaction ? $previousTransaction->saldo_akhir : 0;

    //     // For new transaction or update, calculate the impact on current transaction
    //     if ($action !== 'delete') {
    //         if ($action === 'update' && $oldNominal !== null) {
    //             // For updates, first reverse the impact of old nominal
    //             $saldo = in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
    //                 ? $saldo + $oldNominal  // Add back the old expense
    //                 : $saldo - $oldNominal; // Subtract the old income
    //         }

    //         // Add the impact of current transaction
    //         $saldo = !in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
    //             ? $saldo + $transaksi->nominal
    //             : $saldo - $transaksi->nominal;

    //         // For updates, we need to save the current transaction's balances
    //         if ($action === 'update') {
    //             $transaksi->saldo_awal = $previousTransaction ? $previousTransaction->saldo_akhir : 0;
    //             $transaksi->saldo_akhir = $saldo;
    //             $transaksi->save();
    //         }
    //     }

    //     // Update each future transaction
    //     foreach ($futureTransactions as $futureTransaction) {
    //         $futureTransaction->saldo_awal = $saldo;

    //         // Calculate new ending balance based on transaction type
    //         $saldo = !in_array($futureTransaction->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
    //             ? $saldo + $futureTransaction->nominal
    //             : $saldo - $futureTransaction->nominal;

    //         $futureTransaction->saldo_akhir = $saldo;
    //         $futureTransaction->save();
    //     }

    //     // Update buku kas balance with the final balance
    //     $bukuKas = $transaksi->buku_kas;

    //     if ($action === 'delete') {
    //         // If deleting, get the last remaining transaction
    //         $lastTransaction = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
    //             ->where('id', '!=', $transaksi->id)
    //             ->orderBy('tanggal', 'desc')
    //             ->orderBy('created_at', 'desc')
    //             ->first();

    //         $bukuKas->saldo = $lastTransaction ? $lastTransaction->saldo_akhir : 0;
    //     } else {
    //         // For create/update, use the last calculated balance
    //         // dd(
    //         //     $futureTransactions->isNotEmpty() ? $saldo : $transaksi->saldo_akhir,
    //         //     $futureTransactions->isNotEmpty(),
    //         //     $saldo,
    //         //     $transaksi->saldo_akhir,
    //         // );
    //         $bukuKas->saldo = $futureTransactions->isNotEmpty() ? $saldo : $transaksi->saldo_akhir;
    //     }

    //     $bukuKas->save();
    // }

    public function updateFutureTransactions(
        Transaksi $transaksi,
        string $action = 'create',
        $oldNominal = null
    ) {
        // Ambil transaksi sebelumnya berdasarkan tanggal dan buku kas yang sama
        $previousTransaction = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
            ->when($action !== 'create', function ($query) use ($transaksi) {
                return $query->where('id', '!=', $transaksi->id);
            })
            ->where('tanggal', '<=', $transaksi->tanggal)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        // Ambil transaksi yang ada setelah tanggal transaksi yang baru
        $futureTransactions = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
            ->when($action !== 'create', function ($query) use ($transaksi) {
                return $query->where('id', '!=', $transaksi->id);
            })
            ->where('tanggal', '>', $transaksi->tanggal)
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();

        // Set saldo awal berdasarkan transaksi sebelumnya atau 0 jika tidak ada
        $saldo = $previousTransaction ? $previousTransaction->saldo_akhir : 0;

        // Jika action adalah update, balikkan dampak nominal lama
        if ($action === 'update' && $oldNominal !== null) {
            $saldo = in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
                ? $saldo + $oldNominal  // Tambahkan kembali nominal lama untuk pengeluaran
                : $saldo - $oldNominal;  // Kurangi nominal lama untuk pemasukan
        }

        // Hitung saldo akhir berdasarkan jenis transaksi
        $saldo = !in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
            ? $saldo + $transaksi->nominal
            : $saldo - $transaksi->nominal;

        // Jika action adalah update, simpan saldo awal dan akhir ke transaksi yang diupdate
        if ($action === 'update') {
            $transaksi->saldo_awal = $previousTransaction ? $previousTransaction->saldo_akhir : 0;
            $transaksi->saldo_akhir = $saldo;
            $transaksi->save();
        }

        // Perbarui setiap transaksi yang ada setelah tanggal transaksi yang baru
        foreach ($futureTransactions as $futureTransaction) {
            $futureTransaction->saldo_awal = $saldo;

            // Hitung saldo akhir berdasarkan jenis transaksi
            $saldo = !in_array($futureTransaction->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
                ? $saldo + $futureTransaction->nominal
                : $saldo - $futureTransaction->nominal;

            $futureTransaction->saldo_akhir = $saldo;
            $futureTransaction->save();
        }

        // Jika transaksi adalah yang terakhir berdasarkan tanggal, hentikan eksekusi
        if ($futureTransactions->isEmpty()) {
            return;
        }

        // Perbarui saldo buku kas
        $bukuKas = $transaksi->buku_kas;
        $bukuKas->saldo = $saldo;
        $bukuKas->save();
    }
}
