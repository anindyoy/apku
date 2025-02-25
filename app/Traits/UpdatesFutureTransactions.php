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

        // Cek jika $futureTransactions tidak kosong dan action-nya adalah create
        if (!empty($futureTransactions) && $action === 'create') {
            // Inisialisasi previousTransaction dengan transaksi saat ini
            $previousTransaction = $transaksi;

            foreach ($futureTransactions as $futureTransaction) {
                // Update saldo awal futureTransaction dengan saldo akhir dari transaksi sebelumnya
                $futureTransaction->saldo_awal = $previousTransaction->saldo_akhir;

                // Hitung ulang saldo akhir berdasarkan saldo awal yang baru
                if (in_array($futureTransaction->jenis_transaksi, ['Pengeluaran', 'Transfer Pengeluaran'])) {
                    $futureTransaction->saldo_akhir = $futureTransaction->saldo_awal - $futureTransaction->nominal;
                } else { // kredit
                    $futureTransaction->saldo_akhir = $futureTransaction->saldo_awal + $futureTransaction->nominal;
                }

                // Simpan perubahan
                $futureTransaction->save();

                // Update previousTransaction untuk iterasi berikutnya
                $previousTransaction = $futureTransaction;
            }
        }

        if ($futureTransactions->isEmpty() && $action !== 'delete') {
            $bukuKas = $transaksi->buku_kas;
            $saldo = $previousTransaction ? $previousTransaction->saldo_akhir : 0;

            $saldo = !in_array($transaksi->jenis_transaksi, ['Pengeluaran', 'Transfer Pengeluaran'])
                ? $saldo + $transaksi->nominal
                : $saldo - $transaksi->nominal;
            $bukuKas->saldo = $saldo;

            $bukuKas->save();
            return;
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
