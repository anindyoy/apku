<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class TransaksiService
{
    public function ubah(User $user, Transaksi $transaksi, array $data): Transaksi
    {
        $this->pastikanDapatMengelola($user, $transaksi);

        $proses = function () use ($user, $transaksi, $data): Transaksi {
            $terkunci = Transaksi::withoutGlobalScopes()->whereKey($transaksi->id)->lockForUpdate()->firstOrFail();

            if ($terkunci->transfer_code) {
                return $this->ubahPasanganTransfer($user, $terkunci, $data);
            }

            $dataBaru = array_merge($terkunci->only([
                'buku_kas_id', 'dompet_id', 'jenis_transaksi_id', 'tanggal', 'nominal', 'jenis', 'deskripsi',
            ]), $data);
            $bukuKasBaru = BukuKas::withoutGlobalScopes()->findOrFail($dataBaru['buku_kas_id']);
            $dompetBaru = Dompet::withoutGlobalScopes()->findOrFail($dataBaru['dompet_id']);

            $this->pastikanTujuanDapatDikelola($user, $bukuKasBaru, $dompetBaru);
            $this->balikDampak($terkunci);
            Transaksi::withoutEvents(fn () => $terkunci->update($dataBaru));
            $this->terapkanDampak($terkunci->fresh());

            return $terkunci->fresh();
        };

        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }

    public function hapus(User $user, Transaksi $transaksi): bool
    {
        $this->pastikanDapatMengelola($user, $transaksi);

        $proses = function () use ($transaksi): bool {
            $query = $transaksi->transfer_code
                ? Transaksi::withoutGlobalScopes()->where('transfer_code', $transaksi->transfer_code)
                : Transaksi::withoutGlobalScopes()->whereKey($transaksi->id);
            $semua = $query->orderBy('id')->lockForUpdate()->get();

            foreach ($semua as $item) {
                $this->balikDampak($item);
            }

            Transaksi::withoutEvents(function () use ($semua): void {
                foreach ($semua as $item) {
                    $item->delete();
                }
            });

            return true;
        };

        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }

    private function ubahPasanganTransfer(User $user, Transaksi $transaksi, array $data): Transaksi
    {
        $pasangan = Transaksi::withoutGlobalScopes()
            ->where('transfer_code', $transaksi->transfer_code)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($pasangan->count() !== 2) {
            throw new \RuntimeException('Pasangan transaksi transfer tidak lengkap.');
        }

        foreach ($pasangan as $item) {
            $this->pastikanDapatMengelola($user, $item);
            $this->balikDampak($item);
            Transaksi::withoutEvents(fn () => $item->update([
                'nominal' => $data['nominal'] ?? $item->nominal,
                'tanggal' => $data['tanggal'] ?? $item->tanggal,
                'deskripsi' => $data['deskripsi'] ?? $item->deskripsi,
            ]));
            $this->terapkanDampak($item->fresh());
        }

        return Transaksi::withoutGlobalScopes()->findOrFail($transaksi->id);
    }

    private function pastikanDapatMengelola(User $user, Transaksi $transaksi): void
    {
        $bukuKas = BukuKas::withoutGlobalScopes()->findOrFail($transaksi->buku_kas_id);
        $dompet = Dompet::withoutGlobalScopes()->withTrashed()->findOrFail($transaksi->dompet_id);
        $this->pastikanTujuanDapatDikelola($user, $bukuKas, $dompet);
    }

    private function pastikanTujuanDapatDikelola(User $user, BukuKas $bukuKas, Dompet $dompet): void
    {
        if (
            $bukuKas->user_id !== $user->id
            || $dompet->user_id !== $user->id
            || $dompet->trashed()
            || ! $user->dapatMengelolaTransaksiPada($bukuKas)
            || ! $user->dapatMengelolaTransaksiPadaDompet($dompet)
        ) {
            throw new AuthorizationException('Transaksi tidak dapat dikelola.');
        }
    }

    private function balikDampak(Transaksi $transaksi): void
    {
        $dampak = $this->dampak($transaksi);
        BukuKas::withoutGlobalScopes()->whereKey($transaksi->buku_kas_id)->decrement('saldo', $dampak);
        Dompet::withoutGlobalScopes()->withTrashed()->whereKey($transaksi->dompet_id)->decrement('saldo', $dampak);
    }

    private function terapkanDampak(Transaksi $transaksi): void
    {
        $dampak = $this->dampak($transaksi);
        BukuKas::withoutGlobalScopes()->whereKey($transaksi->buku_kas_id)->increment('saldo', $dampak);
        Dompet::withoutGlobalScopes()->withTrashed()->whereKey($transaksi->dompet_id)->increment('saldo', $dampak);
    }

    private function dampak(Transaksi $transaksi): int
    {
        return in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'], true)
            ? -$transaksi->nominal
            : $transaksi->nominal;
    }
}
