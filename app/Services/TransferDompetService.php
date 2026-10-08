<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\SumberDana;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransferDompetService
{
    /** @return array{keluar: Transaksi, masuk: Transaksi} */
    public function transfer(
        User $user,
        SumberDana $sumberDanaAsal,
        SumberDana $sumberDanaTujuan,
        BukuKas $bukuKas,
        int $nominal,
        mixed $tanggal = null,
        ?string $deskripsi = null,
    ): array {
        if ($nominal <= 0) {
            throw ValidationException::withMessages(['nominal' => 'Nominal transfer harus lebih dari nol.']);
        }

        if ($sumberDanaAsal->is($sumberDanaTujuan)) {
            throw ValidationException::withMessages(['sumber_dana_tujuan_id' => 'Sumber dana tujuan harus berbeda dari sumber dana asal.']);
        }

        if (
            $sumberDanaAsal->user_id !== $user->id
            || $sumberDanaTujuan->user_id !== $user->id
            || $bukuKas->user_id !== $user->id
            || ! $user->dapatMengelolaTransaksiPadaDompet($sumberDanaTujuan)
            || ! $user->dapatMengelolaTransaksiPada($bukuKas)
        ) {
            throw new AuthorizationException('Sumber dana atau kas tidak dapat dikelola.');
        }

        $prosesTransfer = function () use ($user, $sumberDanaAsal, $sumberDanaTujuan, $bukuKas, $nominal, $tanggal, $deskripsi): array {
            $sumberDana = SumberDana::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->whereKey([$sumberDanaAsal->id, $sumberDanaTujuan->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $asal = $sumberDana->get($sumberDanaAsal->id);
            $tujuan = $sumberDana->get($sumberDanaTujuan->id);

            if (! $asal || ! $tujuan) {
                throw new AuthorizationException('Sumber dana tidak tersedia.');
            }

            $kodeTransfer = (string) Str::uuid();
            $waktuTransfer = $tanggal ?: now();

            $transaksi = Transaksi::withoutEvents(function () use ($user, $asal, $tujuan, $bukuKas, $nominal, $waktuTransfer, $deskripsi, $kodeTransfer): array {
                $data = [
                    'user_id' => $user->id,
                    'buku_kas_id' => $bukuKas->id,
                    'tanggal' => $waktuTransfer,
                    'nominal' => $nominal,
                    'transfer_code' => $kodeTransfer,
                    'tipe_transfer' => 'dompet',
                    'deskripsi' => $deskripsi,
                ];

                return [
                    'keluar' => Transaksi::create($data + [
                        'sumber_dana_id' => $asal->id,
                        'jenis' => 'Transfer Pengeluaran',
                    ]),
                    'masuk' => Transaksi::create($data + [
                        'sumber_dana_id' => $tujuan->id,
                        'jenis' => 'Transfer Pemasukan',
                    ]),
                ];
            });

            $asal->decrement('saldo', $nominal);
            $tujuan->increment('saldo', $nominal);

            return $transaksi;
        };

        return DB::transactionLevel() > 0 ? $prosesTransfer() : DB::transaction($prosesTransfer);
    }

    public function pindahkanSaldoDanHapus(
        User $user,
        SumberDana $sumberDanaAsal,
        SumberDana $sumberDanaTujuan,
        BukuKas $bukuKas,
    ): void {
        if ($user->sumberDana()->count() <= 1) {
            throw ValidationException::withMessages(['sumber_dana_tujuan_id' => 'Sumber dana terakhir tidak dapat dihapus.']);
        }

        if ($sumberDanaAsal->is_default) {
            throw ValidationException::withMessages(['sumber_dana_tujuan_id' => 'Sumber dana default tidak dapat dihapus.']);
        }

        if ($sumberDanaAsal->is($sumberDanaTujuan)) {
            throw ValidationException::withMessages(['sumber_dana_tujuan_id' => 'Sumber dana tujuan harus berbeda dari sumber dana yang dihapus.']);
        }

        if (
            $sumberDanaAsal->user_id !== $user->id
            || $sumberDanaTujuan->user_id !== $user->id
            || $bukuKas->user_id !== $user->id
            || ! $user->dapatMengelolaTransaksiPadaDompet($sumberDanaAsal)
            || ! $user->dapatMengelolaTransaksiPadaDompet($sumberDanaTujuan)
            || ! $user->dapatMengelolaTransaksiPada($bukuKas)
        ) {
            throw new AuthorizationException('Sumber dana atau kas tidak dapat dikelola.');
        }

        $prosesPenghapusan = function () use ($user, $sumberDanaAsal, $sumberDanaTujuan, $bukuKas): void {
            $sumberDana = SumberDana::withoutGlobalScopes()
                ->whereKey([$sumberDanaAsal->id, $sumberDanaTujuan->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $asal = $sumberDana->get($sumberDanaAsal->id);
            $tujuan = $sumberDana->get($sumberDanaTujuan->id);

            if (! $asal || ! $tujuan) {
                throw new AuthorizationException('Sumber dana tidak tersedia.');
            }

            $saldo = (int) $asal->saldo;

            if ($saldo > 0) {
                $this->transfer($user, $asal, $tujuan, $bukuKas, $saldo, now(), 'Pemindahan saldo sebelum penghapusan sumber dana');
            } elseif ($saldo < 0) {
                $this->transfer($user, $tujuan, $asal, $bukuKas, abs($saldo), now(), 'Pemindahan kewajiban sebelum penghapusan sumber dana');
            }

            $asal->refresh();

            if ((int) $asal->saldo !== 0) {
                throw ValidationException::withMessages(['sumber_dana_tujuan_id' => 'Saldo sumber dana asal gagal dipindahkan.']);
            }

            $asal->delete();
        };

        if (DB::transactionLevel() > 0) {
            $prosesPenghapusan();
        } else {
            DB::transaction($prosesPenghapusan);
        }
    }
}
