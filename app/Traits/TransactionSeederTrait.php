<?php

namespace App\Traits;

use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;

trait TransactionSeederTrait
{
    public function seedTransactions($user_id, $jumlah = null)
    {
        for ($i = 0; $i < $jumlah ?? rand(15, 25); $i++) {
            $kas = BukuKas::getRandomBukuKas($user_id)->first();
            $jenisRandom = rand(0, 5);

            if ($jenisRandom > 4) { // Transfer transaction
                $tujuanKas = BukuKas::getRandomBukuKas($user_id)
                    ->where('id', '!=', $kas->id)
                    ->first();

                if (!$tujuanKas) {
                    continue; // Skip if no other BukuKas found for transfer
                }

                $transfer_code = uniqid(); // Generate a unique transfer code
                $nominal = rand(1, 100);
                $tanggal = fake()->dateTimeBetween(
                    $kas->transaksi()->first()->tanggal,
                    'now'
                );

                Transaksi::create([
                    'user_id' => $user_id,
                    'buku_kas_id' => $kas->id,
                    'tanggal' => $tanggal,
                    'nominal' => $nominal,
                    'jenis' => 'Transfer Pengeluaran',
                    'transfer_code' => $transfer_code,
                    'tujuan_buku_tabungan_id' => $tujuanKas->id,
                    'deskripsi' => 'Transfer ke ' . $tujuanKas->nama_buku,
                ]);

                Transaksi::create([
                    'user_id' => $user_id,
                    'buku_kas_id' => $tujuanKas->id,
                    'tanggal' => $tanggal,
                    'nominal' => $nominal,
                    'jenis' => 'Transfer Pemasukan',
                    'transfer_code' => $transfer_code,
                    'asal_buku_tabungan_id' => $kas->id,
                    'deskripsi' => 'Transfer dari ' . $kas->nama_buku,
                ]);

                $kas->saldo -= $nominal;
                $kas->save();
                $tujuanKas->saldo += $nominal;
                $tujuanKas->save();
            } else { // Regular transaction (Pemasukan/Pengeluaran)
                $jenis = ($jenisRandom % 2 == 0) ? 'Pengeluaran' : 'Pemasukan';
                $jenis_transaksi_id = JenisTransaksi::whereUserId($user_id)
                    ->whereTipe($jenis)
                    ->inRandomOrder()
                    ->first()
                    ->id;

                $transaksi = Transaksi::factory()->create([
                    'user_id' => $user_id,
                    'buku_kas_id' => $kas->id,
                    'tanggal' => fake()->dateTimeBetween(
                        $kas->transaksi()->first()->tanggal,
                        'now'
                    ),
                    'jenis_transaksi_id' => $jenis_transaksi_id,
                    'jenis' => $jenis,
                    'deskripsi' => fake()->optional()->words(rand(2, 5), true)
                ]);

                if ($transaksi->jenis == 'Pengeluaran') {
                    $kas->saldo -= $transaksi->nominal;
                    $kas->save();
                } else {
                    $kas->saldo += $transaksi->nominal;
                    $kas->save();
                }
            }
        }
    }
}
