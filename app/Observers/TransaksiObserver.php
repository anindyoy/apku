<?php

namespace App\Observers;

use App\Models\BukuKas;
use App\Models\Transaksi;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TransaksiObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Transaksi "created" event.
     */
    public function created(Transaksi $transaksi): void
    {
        if (
            $transaksi->created_at !=
            $transaksi->buku_kas->created_at
        ) {
            $kas = $transaksi->buku_kas;
            if (in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])) {
                $kas->saldo -= $transaksi->nominal;
                $kas->save();
            } else {
                $kas->saldo += $transaksi->nominal;
                $kas->save();
            }
        }
    }

    /**
     * Handle the Transaksi "updated" event.
     */
    // public function updated(Transaksi $transaksi): void
    // {
    //     $kas = $transaksi->buku_kas;
    //     if (in_array($transaksi->jenis, ['Transfer Pengeluaran', 'Pengeluaran'])) {
    //         $kas->saldo = $kas->saldo + $transaksi->getOriginal('nominal') - $transaksi->nominal;
    //         $kas->save();
    //     } else {
    //         $kas->saldo = $kas->saldo - $transaksi->getOriginal('nominal') + $transaksi->nominal;
    //         $kas->save();
    //     }
    // }

    public function updated(Transaksi $transaksi): void
    {
        $kas = $transaksi->buku_kas;

        if (in_array($transaksi->jenis, ['Transfer Pengeluaran', 'Transfer Pemasukan']) && $transaksi->isDirty('nominal')) {
            // Get related transactions with the same transfer code
            $relatedTransactions = Transaksi::where('transfer_code', $transaksi->transfer_code)->get();

            foreach ($relatedTransactions as $relatedTransaction) {
                $relatedKas = $relatedTransaction->buku_kas;

                if ($relatedTransaction->jenis === 'Transfer Pengeluaran') {
                    $relatedKas->saldo = $relatedKas->saldo + $relatedTransaction->getOriginal('nominal') - $relatedTransaction->nominal;
                } else if ($relatedTransaction->jenis === 'Transfer Pemasukan') {
                    $relatedKas->saldo = $relatedKas->saldo - $relatedTransaction->getOriginal('nominal') + $relatedTransaction->nominal;
                }

                $relatedKas->save();

                if ($relatedTransaction->id != $transaksi->id) {
                    $relatedTransaction->nominal = $transaksi->nominal;
                    $relatedTransaction->save();
                }
            }
        } else if (in_array($transaksi->jenis, ['Pengeluaran', 'Pemasukan']) && $transaksi->isDirty('nominal')) {
            // Handle non-transfer transactions
            if ($transaksi->jenis == 'Pengeluaran') {
                $kas->saldo = $kas->saldo + $transaksi->getOriginal('nominal') - $transaksi->nominal;
            } else {
                $kas->saldo = $kas->saldo - $transaksi->getOriginal('nominal') + $transaksi->nominal;
            }
            $kas->save();
        }
    }


    /**
     * Handle the Transaksi "deleted" event.
     */
    public function deleted(Transaksi $transaksi): void
    {
        // $kas = $transaksi->buku_kas;
        // if (in_array($transaksi->jenis, ['Transfer Pengeluaran', 'Transfer Pemasukan'])) {
        //     $kas = $transaksi->buku_kas;

        //     if ($transaksi->jenis === 'Transfer Pengeluaran') {
        //         $kas->saldo = $kas->saldo + $transaksi->nominal;
        //     } else if ($transaksi->jenis === 'Transfer Pemasukan') {
        //         $kas->saldo = $kas->saldo - $transaksi->nominal;
        //     }

        //     $kas->save();

        //     $relatedTransaction = Transaksi::where('transfer_code', $transaksi->transfer_code)->first();

        //     if ($relatedTransaction) {
        //         $relatedKas = $relatedTransaction->buku_kas;

        //         if ($relatedTransaction->jenis === 'Transfer Pengeluaran') {
        //             $relatedKas->saldo += $relatedTransaction->nominal;
        //         } else
        //             $relatedKas->saldo -= $relatedTransaction->nominal;

        //         $relatedKas->save();
        //         $relatedTransaction->delete();
        //     }
        // } else if (in_array($transaksi->jenis, ['Pengeluaran', 'Pemasukan'])) {
        //     if ($transaksi->jenis == 'Pengeluaran') {
        //         $kas->saldo = $kas->saldo + $transaksi->nominal;
        //     } else {
        //         $kas->saldo = $kas->saldo - $transaksi->nominal;
        //     }

        //     $kas->save();
        // }
    }

    /**
     * Handle the Transaksi "restored" event.
     */
    public function restored(Transaksi $transaksi): void
    {
        //
    }

    /**
     * Handle the Transaksi "force deleted" event.
     */
    public function forceDeleted(Transaksi $transaksi): void
    {
        //
    }
}
