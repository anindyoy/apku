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
        $this->command->info('🗑️  Truncating tables...');
        BukuKas::truncate();
        Transaksi::truncate();

        $users = User::whereNot('id', 1)->get();
        $this->command->info("👥 Found {$users->count()} users to seed");
        $this->command->newLine();

        foreach ($users as $value) {
            $this->command->info("📊 Processing User ID: {$value->id}");

            // CREATE BUKU KAS & TRANSAKSI PERTAMA
            $bukuKasCount = rand(2, 4);
            $this->command->line("   Creating {$bukuKasCount} Buku Kas...");

            for ($i = 0; $i < $bukuKasCount; $i++) {
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
            $transactionCount = $value->id == 2 ? 50 : null;
            $this->command->line("   Creating transactions...");
            $this->seedTransactions($value->id, $transactionCount);

            if ($value->id == 2) {
                $currentMonth = now()->month;
                $currentYear = now()->year;

                $hasCurrentMonthTransaction = Transaksi::where('user_id', $value->id)
                    ->whereBukuKasId(1)
                    ->whereMonth('tanggal', $currentMonth)
                    ->whereYear('tanggal', $currentYear)
                    ->count();

                if ($hasCurrentMonthTransaction < 5) {
                    $this->command->line("   Adding current month transactions...");
                    $this->seedTransactions($value->id, 5, now()->startOfMonth());
                }
            }

            $this->command->newLine();
        }

        // Summary
        $totalBukuKas = BukuKas::count();
        $totalTransaksi = Transaksi::count();

        $this->command->info("✅ Seeding completed successfully!");
        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Users Processed', $users->count()],
                ['Buku Kas Created', $totalBukuKas],
                ['Transactions Created', $totalTransaksi],
            ]
        );
    }
}