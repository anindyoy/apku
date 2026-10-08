<?php

namespace App\Observers;

use App\Models\SumberDana;
use App\Models\Transaksi;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Jalur kompatibilitas untuk penulisan model secara langsung.
 *
 * Seluruh alur transaksi aplikasi wajib menggunakan service domain yang
 * menonaktifkan event model agar perubahan saldo tidak dihitung dua kali.
 */
class TransaksiObserver implements ShouldHandleEventsAfterCommit
{
    /** Menangani event pembuatan transaksi. */
    public function created(Transaksi $transaksi): void
    {
        $kas = $transaksi->buku_kas;
        $sumberDana = $transaksi->sumberDana;
        $dampak = in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])
            ? -$transaksi->nominal
            : $transaksi->nominal;

        $saldoSudahMasukBukuKas = $transaksi->created_at == $kas->created_at;
        $saldoSudahMasukSumberDana = $transaksi->deskripsi === 'Saldo awal'
            && $transaksi->created_at == $sumberDana->created_at;

        if (! $saldoSudahMasukBukuKas) {
            $kas->increment('saldo', $dampak);
        }

        if (! $saldoSudahMasukSumberDana) {
            $sumberDana->increment('saldo', $dampak);
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

        if ($transaksi->isDirty(['nominal', 'jenis', 'sumber_dana_id'])) {
            $nominalLama = (int) $transaksi->getOriginal('nominal');
            $jenisLama = $transaksi->getOriginal('jenis');
            $sumberDanaAsal = SumberDana::withoutGlobalScopes()->find($transaksi->getOriginal('sumber_dana_id'));
            $sumberDanaBaru = SumberDana::withoutGlobalScopes()->find($transaksi->sumber_dana_id);

            if ($sumberDanaAsal) {
                $dampakLama = in_array($jenisLama, ['Pengeluaran', 'Transfer Pengeluaran']) ? -$nominalLama : $nominalLama;
                $sumberDanaAsal->decrement('saldo', $dampakLama);
            }

            if ($sumberDanaBaru) {
                $dampakBaru = in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran']) ? -$transaksi->nominal : $transaksi->nominal;
                $sumberDanaBaru->increment('saldo', $dampakBaru);
            }
        }
    }

    /** Menangani event penghapusan transaksi. */
    public function deleted(Transaksi $transaksi): void
    {
        $sumberDana = $transaksi->sumberDana;

        if (! $sumberDana) {
            return;
        }

        if (in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'])) {
            $sumberDana->increment('saldo', $transaksi->nominal);
        } else {
            $sumberDana->decrement('saldo', $transaksi->nominal);
        }
    }

    /** Menangani event pemulihan transaksi. */
    public function restored(Transaksi $transaksi): void {}

    /** Menangani event penghapusan permanen transaksi. */
    public function forceDeleted(Transaksi $transaksi): void {}
}
