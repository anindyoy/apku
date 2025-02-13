<?php

namespace App\Observers;

use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Traits\UpdatesFutureTransactions;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TransaksiObserver implements ShouldHandleEventsAfterCommit
{
    use UpdatesFutureTransactions;
    /**
     * Handle the Transaksi "created" event.
     */
    // public function created(Transaksi $transaksi): void
    // {
    //     // // JIKA MENGGUNAKAN KOLOM SALDO DI TRANSAKSI ============
    //     $bukuKas = $transaksi->buku_kas;
    //     $saldoAwal = $bukuKas->saldo;
    //     $saldoAkhir = !in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
    //         ? $saldoAwal + $transaksi->nominal
    //         : $saldoAwal - $transaksi->nominal;

    //     // Simpan saldo ke transaksi
    //     $transaksi->saldo_awal = $saldoAwal;
    //     $transaksi->saldo_akhir = $saldoAkhir;
    //     $transaksi->save();

    //     // Perbarui saldo buku kas
    //     $bukuKas->saldo = $saldoAkhir;
    //     $bukuKas->save();

    //     $this->updateFutureTransactions($transaksi);
    // }

    public function created(Transaksi $transaksi): void
    {
        // Ambil buku kas yang terkait dengan transaksi
        $bukuKas = $transaksi->buku_kas;

        // Cari transaksi sebelumnya berdasarkan tanggal dan buku kas yang sama
        $previousTransaction = Transaksi::where('buku_kas_id', $transaksi->buku_kas_id)
            ->where('tanggal', '<=', $transaksi->tanggal)
            ->where('id', '!=', $transaksi->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        // Hitung saldo awal berdasarkan transaksi sebelumnya atau 0 jika tidak ada
        $saldoAwal = $previousTransaction ? $previousTransaction->saldo_akhir : 0;

        // Hitung saldo akhir berdasarkan jenis transaksi
        $saldoAkhir = !in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
            ? $saldoAwal + $transaksi->nominal
            : $saldoAwal - $transaksi->nominal;

        // Simpan saldo awal dan akhir ke transaksi
        $transaksi->saldo_awal = $saldoAwal;
        $transaksi->saldo_akhir = $saldoAkhir;
        $transaksi->save();

        // Perbarui saldo buku kas
        $bukuKas->saldo = $saldoAkhir;
        $bukuKas->save();
        
        // Perbarui transaksi yang ada setelah tanggal transaksi yang baru
        $this->updateFutureTransactions($transaksi);
    }

    public function updated(Transaksi $transaksi): void
    {
        // if (in_array($transaksi->jenis, ['Pengeluaran', 'Pemasukan']) && $transaksi->isDirty('nominal')) {
        //     $kas = $transaksi->buku_kas;
        //     // Handle non-transfer transactions
        //     if ($transaksi->jenis == 'Pengeluaran') {
        //         $kas->saldo = $kas->saldo + $transaksi->getOriginal('nominal') - $transaksi->nominal;
        //     } else {
        //         $kas->saldo = $kas->saldo - $transaksi->getOriginal('nominal') + $transaksi->nominal;
        //     }
        //     $kas->save();
        // }
    }


    /**
     * Handle the Transaksi "deleted" event.
     */
    public function deleted(Transaksi $transaksi): void
    {
        // $bukuKas = $transaksi->buku_kas;
        // $penyesuaian = in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
        //     ? -$transaksi->nominal
        //     : $transaksi->nominal;

        // // Perbarui saldo buku kas
        // $bukuKas->saldo += $penyesuaian;
        // $bukuKas->save();

        // // Perbarui saldo transaksi setelahnya
        // $this->updateFutureTransactions($transaksi, $penyesuaian);
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
