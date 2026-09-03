<?php

namespace App\Observers;

use App\Models\Dompet;
use App\Models\Transaksi;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TransaksiObserver implements ShouldHandleEventsAfterCommit
{
    /** Menangani event pembuatan transaksi. */
    public function created(Transaksi $transaksi): void
    {
        $kas = $transaksi->buku_kas;
        $dompet = $transaksi->dompet;
        $dampak = in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
            ? -$transaksi->nominal
            : $transaksi->nominal;

        $saldoSudahMasukBukuKas = $transaksi->created_at == $kas->created_at;
        $saldoSudahMasukDompet = $transaksi->deskripsi === 'Saldo awal'
            && $transaksi->created_at == $dompet->created_at;

        if (! $saldoSudahMasukBukuKas) {
            $kas->increment('saldo', $dampak);
        }

        if (! $saldoSudahMasukDompet) {
            $dompet->increment('saldo', $dampak);
        }
    }

    /** Menangani event perubahan transaksi. */
    public function updated(Transaksi $transaksi): void
    {
        if (in_array($transaksi->jenis, ['Pengeluaran', 'Pemasukan']) && $transaksi->isDirty('nominal')) {
            $kas = $transaksi->buku_kas;
            // Sesuaikan saldo untuk transaksi selain transfer.
            if ($transaksi->jenis == 'Pengeluaran') {
                $kas->saldo = $kas->saldo + $transaksi->getOriginal('nominal') - $transaksi->nominal;
            } else {
                $kas->saldo = $kas->saldo - $transaksi->getOriginal('nominal') + $transaksi->nominal;
            }
            $kas->save();
        }

        if ($transaksi->isDirty(['nominal', 'jenis', 'dompet_id'])) {
            $nominalLama = (int) $transaksi->getOriginal('nominal');
            $jenisLama = $transaksi->getOriginal('jenis');
            $dompetAsal = Dompet::withoutGlobalScopes()->find($transaksi->getOriginal('dompet_id'));
            $dompetBaru = Dompet::withoutGlobalScopes()->find($transaksi->dompet_id);

            if ($dompetAsal) {
                $dampakLama = in_array($jenisLama, ['Pengeluaran', 'Transfer Pengeluaran']) ? -$nominalLama : $nominalLama;
                $dompetAsal->decrement('saldo', $dampakLama);
            }

            if ($dompetBaru) {
                $dampakBaru = in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran']) ? -$transaksi->nominal : $transaksi->nominal;
                $dompetBaru->increment('saldo', $dampakBaru);
            }
        }
    }

    /** Menangani event penghapusan transaksi. */
    public function deleted(Transaksi $transaksi): void
    {
        $dompet = $transaksi->dompet;

        if (! $dompet) {
            return;
        }

        if (in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])) {
            $dompet->increment('saldo', $transaksi->nominal);
        } else {
            $dompet->decrement('saldo', $transaksi->nominal);
        }
    }

    /** Menangani event pemulihan transaksi. */
    public function restored(Transaksi $transaksi): void {}

    /** Menangani event penghapusan permanen transaksi. */
    public function forceDeleted(Transaksi $transaksi): void {}
}
