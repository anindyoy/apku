<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;

trait TransactionSeederTrait
{
    public function seedTransactions($user_id, $jumlah = null, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->subDays(rand(30, 60));
        $endDate = $endDate ? Carbon::parse($endDate) : $startDate->copy()->addDays($jumlah ?? rand(15, 25));

        if ($endDate->gt(now())) {
            $endDate = now();
        }

        // Ambil semua buku kas berdasarkan user_id
        $bukuKasList = BukuKas::where('user_id', $user_id)->get();

        // Create progress bar for buku kas
        $progressBar = $this->command->getOutput()->createProgressBar($bukuKasList->count());
        $progressBar->setFormat('   %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Processing Buku Kas...');
        $progressBar->start();

        foreach ($bukuKasList as $kas) {
            $progressBar->setMessage("Buku Kas: {$kas->nama_buku}");

            // Jika user_id adalah 2 dan buku_kas_id adalah 1, pastikan minimal ada 20 transaksi
            if ($user_id == 2 && $kas->id == 1) {
                $existingTransactionsCount = Transaksi::where('user_id', $user_id)
                    ->where('buku_kas_id', $kas->id)
                    ->count();

                if ($existingTransactionsCount < 20) {
                    $missingTransactions = 20 - $existingTransactionsCount;

                    for ($i = 0; $i < $missingTransactions; $i++) {
                        $currentDate = now()->addMinutes(rand(10, 1440));

                        $jenis = rand(0, 1) ? 'Pemasukan' : 'Pengeluaran';
                        $jenis_transaksi_id = JenisTransaksi::whereUserId($user_id)
                            ->whereTipe($jenis)
                            ->inRandomOrder()
                            ->first()
                            ->id;

                        $transaksi = Transaksi::factory()->create([
                            'user_id' => $user_id,
                            'buku_kas_id' => $kas->id,
                            'tanggal' => $currentDate,
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

            // Pastikan setiap buku kas memiliki minimal 10 transaksi
            $existingTransactionsCount = Transaksi::where('user_id', $user_id)
                ->where('buku_kas_id', $kas->id)
                ->count();

            if ($existingTransactionsCount < 10) {
                $missingTransactions = 10 - $existingTransactionsCount;

                for ($i = 0; $i < $missingTransactions; $i++) {
                    $currentDate = now()->addMinutes(rand(10, 1440));

                    $jenis = rand(0, 1) ? 'Pemasukan' : 'Pengeluaran';
                    $jenis_transaksi_id = JenisTransaksi::whereUserId($user_id)
                        ->whereTipe($jenis)
                        ->inRandomOrder()
                        ->first()
                        ->id;

                    $transaksi = Transaksi::factory()->create([
                        'user_id' => $user_id,
                        'buku_kas_id' => $kas->id,
                        'tanggal' => $currentDate,
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

            // Seed additional transactions based on the provided parameters
            $lastSaldoPertama = Transaksi::where('buku_kas_id', $kas->id)
                ->where('deskripsi', 'Saldo pertama')
                ->first();

            $currentDate = $lastSaldoPertama ? Carbon::parse($lastSaldoPertama->tanggal)->addMinutes(1) : $startDate->copy();

            for ($i = 0; $i < ($jumlah ?? rand(15, 25)); $i++) {
                if ($currentDate->gt($endDate)) {
                    break;
                }

                $currentDate = $currentDate->addMinutes(rand(10, 1440));

                $jenisRandom = rand(0, 5);

                if ($jenisRandom > 4) {
                    // Handle transfer transactions (commented out for now)
                } else {
                    $jenis = ($jenisRandom % 2 == 0) ? 'Pengeluaran' : 'Pemasukan';
                    $jenis_transaksi_id = JenisTransaksi::whereUserId($user_id)
                        ->whereTipe($jenis)
                        ->inRandomOrder()
                        ->first()
                        ->id;

                    $transaksi = Transaksi::factory()->create([
                        'user_id' => $user_id,
                        'buku_kas_id' => $kas->id,
                        'tanggal' => $currentDate,
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

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine();
    }
}