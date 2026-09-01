<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Optimizations vs original:
     * 1. Pre-loads JenisTransaksi into memory (grouped by user/tipe) — eliminates N+1 queries
     * 2. Uses DB::table()->insert() for bulk inserts instead of individual ::create() calls
     * 3. Loads BukuKas per user into a Collection and uses ->randomItem() — eliminates per-iteration getRandomBukuKas() queries
     * 4. Batches saldo updates at the end — reduces UPDATE queries from O(n) to O(books) per user
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Delete existing data & reset auto-increment (truncate alone may not reset AI with ScopedBy)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('transaksi')->delete();
        DB::table('buku_kas')->delete();
        DB::statement('ALTER TABLE transaksi AUTO_INCREMENT=1');
        DB::statement('ALTER TABLE buku_kas AUTO_INCREMENT=1');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $users = User::all()->except(1);

        // Pre-load all JenisTransaksi grouped by [user_id][tipe] to avoid per-iteration queries
        $jenisByUser = JenisTransaksi::all()->groupBy(fn ($jt) => "{$jt->user_id}_{$jt->tipe}");

        foreach ($users as $user) {
            $bukuKasRows = [];
            $bukuSaldos = [];
            $transaksiRows = [];
            $bukuIdMap = []; // old_index => actual_id

            // --- Phase 1: Create BukuKas & initial Transaksi (Pemasukan "Saldo pertama") ---
            $bukuCount = rand(2, 4);

            for ($i = 0; $i < $bukuCount; $i++) {
                $nominal = rand(1, 100);

                $bukuKasRows[] = [
                    'user_id' => $user->id,
                    'nama_buku' => $i === 0 ? 'Kas Utama' : 'Buku Kas ' . ($i + 1),
                    'saldo' => 0, // Updated after transaksi is inserted
                    'goal' => null,
                    'tanggal_goal' => null,
                    'description' => fake()->sentence(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $transaksiRows[] = [
                    'user_id' => $user->id,
                    'buku_kas_id' => 0, // Placeholder, updated after bulk insert
                    'jenis_transaksi_id' => null,
                    'tanggal' => fake()->dateTimeBetween('-3 weeks', 'now'),
                    'nominal' => $nominal,
                    'jenis' => 'Pemasukan',
                    'transfer_code' => null,
                    'deskripsi' => 'Saldo pertama',
                    'tujuan_buku_tabungan_id' => null,
                    'asal_buku_tabungan_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $bukuSaldos[$i] = $nominal;
            }

            // Bulk insert BukuKas and retrieve generated IDs
            DB::table('buku_kas')->insert($bukuKasRows);
            $insertedBukuIds = DB::table('buku_kas')
                ->where('user_id', $user->id)
                ->pluck('id');

            // Map indices to actual IDs and update transaksi buku_kas_id references
            foreach ($insertedBukuIds as $idx => $actualId) {
                $bukuIdMap[$idx] = $actualId;
                for ($t = 0; $t < $bukuCount; $t++) {
                    if ($t === $idx) {
                        $transaksiRows[$t]['buku_kas_id'] = $actualId;
                    }
                }
            }

            // Bulk insert initial Transaksi
            DB::table('transaksi')->insert($transaksiRows);

            // --- Phase 2: Create additional random transactions ---
            $additionalCount = rand(10, 25);
            $transaksiRows = []; // Reset for phase 2

            for ($i = 0; $i < $additionalCount; $i++) {
                $kasIdx = array_rand($bukuIdMap);
                $kasId = $bukuIdMap[$kasIdx];
                $jenisRandom = rand(0, 5);

                if ($jenisRandom > 4 && count($bukuIdMap) >= 2) {
                    // --- Transfer ---
                    $availableIdxs = array_diff(array_keys($bukuIdMap), [$kasIdx]);
                    $tujuanIdx = $availableIdxs[array_rand($availableIdxs)];
                    $tujuanId = $bukuIdMap[$tujuanIdx];
                    $transferCode = uniqid();
                    $nominal = rand(1, 100);

                    // Get the earliest tanggal from this buku's transaksi for the date range
                    $baseTanggal = DB::table('transaksi')
                        ->where('buku_kas_id', $kasId)
                        ->min('tanggal') ?? '-3 weeks';
                    $tanggal = fake()->dateTimeBetween($baseTanggal, 'now');

                    // Get buku names for descriptions
                    $kasNama = DB::table('buku_kas')->where('id', $kasId)->value('nama_buku');
                    $tujuanNama = DB::table('buku_kas')->where('id', $tujuanId)->value('nama_buku');

                    $transaksiRows[] = [
                        'user_id' => $user->id,
                        'buku_kas_id' => $kasId,
                        'jenis_transaksi_id' => null,
                        'tanggal' => $tanggal,
                        'nominal' => $nominal,
                        'jenis' => 'Transfer Pengeluaran',
                        'transfer_code' => $transferCode,
                        'deskripsi' => 'Transfer ke ' . $tujuanNama,
                        'tujuan_buku_tabungan_id' => $tujuanId,
                        'asal_buku_tabungan_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $transaksiRows[] = [
                        'user_id' => $user->id,
                        'buku_kas_id' => $tujuanId,
                        'jenis_transaksi_id' => null,
                        'tanggal' => $tanggal,
                        'nominal' => $nominal,
                        'jenis' => 'Transfer Pemasukan',
                        'transfer_code' => $transferCode,
                        'deskripsi' => 'Transfer dari ' . $kasNama,
                        'tujuan_buku_tabungan_id' => null,
                        'asal_buku_tabungan_id' => $kasId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $bukuSaldos[$kasIdx] -= $nominal;
                    $bukuSaldos[$tujuanIdx] += $nominal;
                } else {
                    // --- Regular transaction (Pemasukan/Pengeluaran) ---
                    $jenis = ($jenisRandom % 2 === 0) ? 'Pengeluaran' : 'Pemasukan';

                    // Resolve jenis_transaksi_id from pre-loaded cache
                    $cacheKey = "{$user->id}_{$jenis}";
                    $jenisTransaksiId = $jenisByUser[$cacheKey]?->random()?->id;

                    $baseTanggal = DB::table('transaksi')
                        ->where('buku_kas_id', $kasId)
                        ->min('tanggal') ?? '-3 weeks';
                    $nominal = rand(1, 100);

                    $transaksiRows[] = [
                        'user_id' => $user->id,
                        'buku_kas_id' => $kasId,
                        'jenis_transaksi_id' => $jenisTransaksiId,
                        'tanggal' => fake()->dateTimeBetween($baseTanggal, 'now'),
                        'nominal' => $nominal,
                        'jenis' => $jenis,
                        'transfer_code' => null,
                        'deskripsi' => fake()->optional()->words(rand(2, 5), true),
                        'tujuan_buku_tabungan_id' => null,
                        'asal_buku_tabungan_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $bukuSaldos[$kasIdx] += ($jenis === 'Pemasukan' ? $nominal : -$nominal);
                }
            }

            // Bulk insert all additional transaksi for this user
            if (!empty($transaksiRows)) {
                DB::table('transaksi')->insert($transaksiRows);
            }

            // Batch update all BukuKas saldo for this user (1 query per book instead of 1 per transaksi)
            foreach ($bukuSaldos as $bukuIdx => $saldo) {
                DB::table('buku_kas')
                    ->where('id', $bukuIdMap[$bukuIdx])
                    ->update(['saldo' => $saldo, 'updated_at' => $now]);
            }
        }
    }
}
