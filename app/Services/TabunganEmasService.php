<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\TabunganEmas;
use App\Models\TransaksiEmas;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TabunganEmasService
{
    public function __construct(private readonly TransaksiService $transaksiService) {}

    public function catatSaldoAwal(User $user, TabunganEmas $tabungan, float $beratGram, int $totalModal, ?string $catatan = null): TransaksiEmas
    {
        $this->pastikanPemilik($user, $tabungan);
        $this->validasiBeratDanNilai($beratGram, $totalModal);

        $proses = function () use ($user, $tabungan, $beratGram, $totalModal, $catatan): TransaksiEmas {
            $terkunci = TabunganEmas::query()->whereKey($tabungan->id)->lockForUpdate()->firstOrFail();
            $terkunci->increment('berat_gram', $beratGram);
            $terkunci->increment('total_modal', $totalModal);

            return $terkunci->transaksiEmas()->create([
                'user_id' => $user->id,
                'jenis' => 'tambah',
                'tanggal' => now(),
                'berat_gram' => $beratGram,
                'total_rupiah' => $totalModal,
                'catatan' => $catatan ?: 'Saldo awal emas',
            ]);
        };

        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }

    public function beli(User $user, TabunganEmas $tabungan, Dompet $dompet, array $data): TransaksiEmas
    {
        $berat = (float) ($data['berat_gram'] ?? 0);
        $harga = (int) ($data['harga_per_gram'] ?? 0);
        $biaya = (int) ($data['biaya_tambahan'] ?? 0);
        $total = (int) round($berat * $harga) + $biaya;
        $this->validasiMutasi($user, $tabungan, $dompet, $berat, $harga, $biaya);

        $proses = function () use ($user, $tabungan, $dompet, $data, $berat, $harga, $biaya, $total): TransaksiEmas {
            $terkunci = TabunganEmas::query()->whereKey($tabungan->id)->lockForUpdate()->firstOrFail();
            $transaksi = $this->transaksiService->buat($user, [
                'buku_kas_id' => $terkunci->buku_kas_id,
                'dompet_id' => $dompet->id,
                'jenis_transaksi_id' => $data['jenis_transaksi_id'],
                'tanggal' => $data['tanggal'] ?? now(),
                'nominal' => $total,
                'deskripsi' => $data['catatan'] ?? 'Pembelian emas '.$terkunci->nama,
            ], 'Pengeluaran');
            $terkunci->increment('berat_gram', $berat);
            $terkunci->increment('total_modal', $total);

            return $terkunci->transaksiEmas()->create([
                'user_id' => $user->id,
                'transaksi_id' => $transaksi->id,
                'jenis' => 'beli',
                'tanggal' => $data['tanggal'] ?? now(),
                'berat_gram' => $berat,
                'harga_per_gram' => $harga,
                'biaya_tambahan' => $biaya,
                'total_rupiah' => $total,
                'catatan' => $data['catatan'] ?? null,
            ]);
        };

        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }

    public function jual(User $user, TabunganEmas $tabungan, Dompet $dompet, array $data): TransaksiEmas
    {
        $berat = (float) ($data['berat_gram'] ?? 0);
        $harga = (int) ($data['harga_per_gram'] ?? 0);
        $biaya = (int) ($data['biaya_tambahan'] ?? 0);
        $total = (int) round($berat * $harga) - $biaya;
        $this->validasiMutasi($user, $tabungan, $dompet, $berat, $harga, $biaya);

        if ($total <= 0) {
            throw ValidationException::withMessages(['total_rupiah' => 'Total penerimaan penjualan harus lebih dari nol.']);
        }

        $proses = function () use ($user, $tabungan, $dompet, $data, $berat, $harga, $biaya, $total): TransaksiEmas {
            $terkunci = TabunganEmas::query()->whereKey($tabungan->id)->lockForUpdate()->firstOrFail();
            $beratLama = (float) $terkunci->berat_gram;

            if ($berat > $beratLama) {
                throw ValidationException::withMessages(['berat_gram' => 'Berat yang dijual melebihi kepemilikan.']);
            }

            $modalKeluar = abs($berat - $beratLama) < 0.00005
                ? (int) $terkunci->total_modal
                : (int) round((int) $terkunci->total_modal * ($berat / $beratLama));
            $transaksi = $this->transaksiService->buat($user, [
                'buku_kas_id' => $terkunci->buku_kas_id,
                'dompet_id' => $dompet->id,
                'jenis_transaksi_id' => $data['jenis_transaksi_id'],
                'tanggal' => $data['tanggal'] ?? now(),
                'nominal' => $total,
                'deskripsi' => $data['catatan'] ?? 'Penjualan emas '.$terkunci->nama,
            ], 'Pemasukan');
            $terkunci->decrement('berat_gram', $berat);
            $terkunci->decrement('total_modal', $modalKeluar);

            return $terkunci->transaksiEmas()->create([
                'user_id' => $user->id,
                'transaksi_id' => $transaksi->id,
                'jenis' => 'jual',
                'tanggal' => $data['tanggal'] ?? now(),
                'berat_gram' => $berat,
                'harga_per_gram' => $harga,
                'biaya_tambahan' => $biaya,
                'total_rupiah' => $total,
                'catatan' => $data['catatan'] ?? null,
            ]);
        };

        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }

    /** @return array{berat_gram:float,nilai_emas:int,saldo_rupiah:int,total_nilai_kas:int,total_modal:int,untung_rugi:int} */
    public function valuasi(BukuKas $bukuKas, int $hargaPerGram): array
    {
        $berat = (float) $bukuKas->tabunganEmas()->sum('berat_gram');
        $modal = (int) $bukuKas->tabunganEmas()->sum('total_modal');
        $nilai = (int) round($berat * $hargaPerGram);

        return [
            'berat_gram' => $berat,
            'nilai_emas' => $nilai,
            'saldo_rupiah' => (int) $bukuKas->saldo,
            'total_nilai_kas' => (int) $bukuKas->saldo + $nilai,
            'total_modal' => $modal,
            'untung_rugi' => $nilai - $modal,
        ];
    }

    private function validasiMutasi(User $user, TabunganEmas $tabungan, Dompet $dompet, float $berat, int $harga, int $biaya): void
    {
        if (! $user->dapatMengelolaTransaksiPada($tabungan->bukuKas) || ! $user->dapatMengelolaTransaksiPadaDompet($dompet)) {
            throw new AuthorizationException('Tabungan emas atau dompet tidak dapat dikelola.');
        }

        if ($berat <= 0 || $harga <= 0 || $biaya < 0) {
            throw ValidationException::withMessages(['berat_gram' => 'Berat dan harga harus lebih dari nol, serta biaya tidak boleh negatif.']);
        }
    }

    private function pastikanPemilik(User $user, TabunganEmas $tabungan): void
    {
        if ($tabungan->bukuKas->user_id !== $user->id) {
            throw new AuthorizationException('Hanya pemilik kas yang dapat mencatat saldo awal emas.');
        }
    }

    private function validasiBeratDanNilai(float $berat, int $nilai): void
    {
        if ($berat <= 0 || $nilai < 0) {
            throw ValidationException::withMessages(['berat_gram' => 'Berat harus lebih dari nol dan modal tidak boleh negatif.']);
        }
    }
}
