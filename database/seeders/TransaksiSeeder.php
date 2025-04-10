<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BukuKas;
use App\Models\Transaksi;
use Illuminate\Database\Seeder;
use App\Traits\TransactionSeederTrait;

class TransaksiSeeder extends Seeder
{
    use TransactionSeederTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BukuKas::truncate();
        Transaksi::truncate();

        $users = User::whereNot('id', 1)->get();

        foreach ($users as $value) {
            // CREATE BUKU KAS & TRANSAKSI PERTAMA
            for ($i = 0; $i < rand(2, 4); $i++) {
                $buku = BukuKas::factory()->create([
                    'user_id' => $value->id,
                    'nama_buku' => $i == 0 ? 'Kas Utama' : fake()->word(),
                ]);

                $transaksi = Transaksi::factory()->create([
                    'user_id' => $value->id,
                    'buku_kas_id' => $buku->id,
                    'jenis' => 'Pemasukan',
                    'deskripsi' => 'Saldo pertama',
                ]);

                $buku->saldo += $transaksi->nominal;
                $buku->save();
            }

            // CREATE TRANSAKSI
            $this->seedTransactions($value->id, $value->id == 2 ? 50 : null);

            if ($value->id == 2) {
                $currentMonth = now()->month;
                $currentYear = now()->year;

                $hasCurrentMonthTransaction = Transaksi::where('user_id', $value->id)
                    ->whereBukuKasId(1)
                    ->whereMonth('tanggal', $currentMonth)
                    ->whereYear('tanggal', $currentYear)
                    ->count();

                if ($hasCurrentMonthTransaction < 5) {
                    $this->seedTransactions($value->id, 5, now()->startOfMonth());
                }
            }
        }
    }
}