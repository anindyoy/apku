<?php

namespace App\Traits;

use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;

trait TransactionSeederTrait
{
    public function seedTransactions($user_id, $jumlah = null, $startDate = null)
    {
        $lastDate = $startDate ?? now()->subDays(rand(30, 60));

        for ($i = 0; $i < ($jumlah ?? rand(15, 25)); $i++) {
            $kas = BukuKas::getRandomBukuKas($user_id)->first();
            $jenisRandom = rand(0, 5);

            // Increment the date for each transaction
            $lastDate = $lastDate->addMinutes(rand(10, 1440)); // Add between 10 minutes to 1 day

            if ($jenisRandom > 4) { // Transfer transaction
                $tujuanKas = BukuKas::getRandomBukuKas($user_id)
                    ->where('id', '!=', $kas->id)
                    ->first();

                if (!$tujuanKas) {
                    continue; // Skip if no other BukuKas found for transfer
                }

                $transfer_code = uniqid(); // Generate a unique transfer code
                $nominal = rand(1, 100);

                Transaksi::create([
                    'user_id' => $user_id,
                    'buku_kas_id' => $kas->id,
                    'tanggal' => $lastDate,
                    'nominal' => $nominal,
                    'jenis' => 'Transfer Pengeluaran',
                    'transfer_code' => $transfer_code,
                    'tujuan_buku_tabungan_id' => $tujuanKas->id,
                    'deskripsi' => 'Transfer ke ' . $tujuanKas->nama_buku,
                ]);

                Transaksi::create([
                    'user_id' => $user_id,
                    'buku_kas_id' => $tujuanKas->id,
                    'tanggal' => $lastDate,
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
                    'tanggal' => $lastDate,
                    'nominal' => rand(1, 100),
                    'jenis' => $jenis,
                    'jenis_transaksi_id' => $jenis_transaksi_id,
                    'deskripsi' => fake()->sentence,
                ]);

                if ($jenis == 'Pemasukan') {
                    $kas->saldo += $transaksi->nominal;
                } else {
                    $kas->saldo -= $transaksi->nominal;
                }
                $kas->save();
            }
        }
    }
}
