<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\Dompet;
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
        Dompet $dompetAsal,
        Dompet $dompetTujuan,
        BukuKas $bukuKas,
        int $nominal,
        mixed $tanggal = null,
        ?string $deskripsi = null,
    ): array {
        if ($nominal <= 0) {
            throw ValidationException::withMessages(['nominal' => 'Nominal transfer harus lebih dari nol.']);
        }

        if ($dompetAsal->is($dompetTujuan)) {
            throw ValidationException::withMessages(['dompet_tujuan_id' => 'Dompet tujuan harus berbeda dari dompet asal.']);
        }

        if (
            $dompetAsal->user_id !== $user->id
            || $dompetTujuan->user_id !== $user->id
            || $bukuKas->user_id !== $user->id
            || ! $user->dapatMengelolaTransaksiPadaDompet($dompetTujuan)
            || ! $user->dapatMengelolaTransaksiPada($bukuKas)
        ) {
            throw new AuthorizationException('Dompet atau buku kas tidak dapat dikelola.');
        }

        $prosesTransfer = function () use ($user, $dompetAsal, $dompetTujuan, $bukuKas, $nominal, $tanggal, $deskripsi): array {
            $dompet = Dompet::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->whereKey([$dompetAsal->id, $dompetTujuan->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $asal = $dompet->get($dompetAsal->id);
            $tujuan = $dompet->get($dompetTujuan->id);

            if (! $asal || ! $tujuan) {
                throw new AuthorizationException('Dompet tidak tersedia.');
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
                        'dompet_id' => $asal->id,
                        'jenis' => 'Transfer Pengeluaran',
                    ]),
                    'masuk' => Transaksi::create($data + [
                        'dompet_id' => $tujuan->id,
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
        Dompet $dompetAsal,
        Dompet $dompetTujuan,
        BukuKas $bukuKas,
    ): void {
        if ($user->dompet()->count() <= 1) {
            throw ValidationException::withMessages(['dompet_tujuan_id' => 'Dompet terakhir tidak dapat dihapus.']);
        }

        if ($dompetAsal->is_default) {
            throw ValidationException::withMessages(['dompet_tujuan_id' => 'Dompet default tidak dapat dihapus.']);
        }

        if ($dompetAsal->is($dompetTujuan)) {
            throw ValidationException::withMessages(['dompet_tujuan_id' => 'Dompet tujuan harus berbeda dari dompet yang dihapus.']);
        }

        if (
            $dompetAsal->user_id !== $user->id
            || $dompetTujuan->user_id !== $user->id
            || $bukuKas->user_id !== $user->id
            || ! $user->dapatMengelolaTransaksiPadaDompet($dompetAsal)
            || ! $user->dapatMengelolaTransaksiPadaDompet($dompetTujuan)
            || ! $user->dapatMengelolaTransaksiPada($bukuKas)
        ) {
            throw new AuthorizationException('Dompet atau buku kas tidak dapat dikelola.');
        }

        $prosesPenghapusan = function () use ($user, $dompetAsal, $dompetTujuan, $bukuKas): void {
            $dompet = Dompet::withoutGlobalScopes()
                ->whereKey([$dompetAsal->id, $dompetTujuan->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $asal = $dompet->get($dompetAsal->id);
            $tujuan = $dompet->get($dompetTujuan->id);

            if (! $asal || ! $tujuan) {
                throw new AuthorizationException('Dompet tidak tersedia.');
            }

            $saldo = (int) $asal->saldo;

            if ($saldo > 0) {
                $this->transfer($user, $asal, $tujuan, $bukuKas, $saldo, now(), 'Pemindahan saldo sebelum penghapusan dompet');
            } elseif ($saldo < 0) {
                $this->transfer($user, $tujuan, $asal, $bukuKas, abs($saldo), now(), 'Pemindahan kewajiban sebelum penghapusan dompet');
            }

            $asal->refresh();

            if ((int) $asal->saldo !== 0) {
                throw ValidationException::withMessages(['dompet_tujuan_id' => 'Saldo dompet asal gagal dipindahkan.']);
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
