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
    public function updated(Transaksi $transaksi): void
    {
        $kas = $transaksi->buku_kas;
        if ($transaksi->jenis == 'Pengeluaran') {
            $kas->saldo = $kas->saldo + $transaksi->getOriginal('nominal') - $transaksi->nominal;
            $kas->save();
        } else {
            $kas->saldo = $kas->saldo - $transaksi->getOriginal('nominal') + $transaksi->nominal;
            $kas->save();
        }
    }

    /**
     * Handle the Transaksi "deleted" event.
     */
    public function deleted(Transaksi $transaksi): void
    {
        $kas = $transaksi->buku_kas;

        if ($transaksi->jenis == 'Pengeluaran') {
            $kas->saldo = $kas->saldo + $transaksi->nominal;
            $kas->save();
        } else {
            $kas->saldo = $kas->saldo - $transaksi->nominal;
            $kas->save();
        }
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
