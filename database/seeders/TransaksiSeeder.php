<?php

namespace Database\Seeders;

use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransaksiSeeder extends Seeder
{
    /**
     * Menjalankan seeder transaksi yang telah dioptimalkan dengan insert massal
     * dan pemuatan relasi di awal agar tidak menghasilkan query N+1.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Hapus data lama dan atur ulang nomor otomatis.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('transaksi')->delete();
        DB::table('buku_kas')->delete();
        DB::table('dompet')->delete();
        DB::statement('ALTER TABLE transaksi AUTO_INCREMENT=1');
        DB::statement('ALTER TABLE buku_kas AUTO_INCREMENT=1');
        DB::statement('ALTER TABLE dompet AUTO_INCREMENT=1');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $users = User::all()->except(1);

        // Muat jenis transaksi berdasarkan pengguna dan tipe di awal.
        $jenisByUser = JenisTransaksi::all()->groupBy(fn ($jt) => "{$jt->user_id}_{$jt->tipe}");

        foreach ($users as $user) {
            $dompetId = DB::table('dompet')->insertGetId([
                'user_id' => $user->id,
                'nama_dompet' => 'Cash',
                'saldo' => 0,
                'is_default' => true,
                'description' => 'Dompet tunai utama',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $saldoDompet = 0;
            $bukuKasRows = [];
            $bukuSaldos = [];
            $transaksiRows = [];
            $bukuIdMap = []; // Indeks lama dipetakan ke ID sebenarnya.

            // Tahap 1: buat buku kas dan transaksi saldo pertama.
            $bukuCount = rand(2, 4);

            for ($i = 0; $i < $bukuCount; $i++) {
                $nominal = rand(1, 100);

                $bukuKasRows[] = [
                    'user_id' => $user->id,
                    'nama_buku' => $i === 0 ? 'Kas Utama' : 'Buku Kas '.($i + 1),
                    'saldo' => 0, // Diperbarui setelah transaksi disimpan.
                    'is_default' => $i === 0,
                    'goal' => null,
                    'tanggal_goal' => null,
                    'description' => fake()->sentence(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $transaksiRows[] = [
                    'user_id' => $user->id,
                    'buku_kas_id' => 0, // Nilai sementara sebelum insert massal selesai.
                    'dompet_id' => $dompetId,
                    'jenis_transaksi_id' => null,
                    'tanggal' => fake()->dateTimeBetween('-3 weeks', 'now'),
                    'nominal' => $nominal,
                    'jenis' => 'Pemasukan',
                    'transfer_code' => null,
                    'tipe_transfer' => null,
                    'deskripsi' => 'Saldo pertama',
                    'tujuan_buku_tabungan_id' => null,
                    'asal_buku_tabungan_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $bukuSaldos[$i] = $nominal;
                $saldoDompet += $nominal;
            }

            // Simpan buku kas secara massal lalu ambil ID yang dihasilkan.
            DB::table('buku_kas')->insert($bukuKasRows);
            $insertedBukuIds = DB::table('buku_kas')
                ->where('user_id', $user->id)
                ->pluck('id');

            // Petakan indeks ke ID dan perbarui referensi buku kas transaksi.
            foreach ($insertedBukuIds as $idx => $actualId) {
                $bukuIdMap[$idx] = $actualId;
                for ($t = 0; $t < $bukuCount; $t++) {
                    if ($t === $idx) {
                        $transaksiRows[$t]['buku_kas_id'] = $actualId;
                    }
                }
            }

            // Simpan transaksi awal secara massal.
            DB::table('transaksi')->insert($transaksiRows);

            // Tahap 2: buat transaksi acak tambahan.
            $additionalCount = rand(10, 25);
            $transaksiRows = []; // Kosongkan data untuk tahap kedua.

            for ($i = 0; $i < $additionalCount; $i++) {
                $kasIdx = array_rand($bukuIdMap);
                $kasId = $bukuIdMap[$kasIdx];
                $jenisRandom = rand(0, 5);

                if ($jenisRandom > 4 && count($bukuIdMap) >= 2) {
                    // Buat transaksi transfer.
                    $availableIdxs = array_diff(array_keys($bukuIdMap), [$kasIdx]);
                    $tujuanIdx = $availableIdxs[array_rand($availableIdxs)];
                    $tujuanId = $bukuIdMap[$tujuanIdx];
                    $transferCode = (string) Str::uuid();
                    $nominal = rand(1, 100);

                    // Ambil tanggal transaksi paling awal sebagai batas rentang.
                    $baseTanggal = DB::table('transaksi')
                        ->where('buku_kas_id', $kasId)
                        ->min('tanggal') ?? '-3 weeks';
                    $tanggal = fake()->dateTimeBetween($baseTanggal, 'now');

                    // Ambil nama buku untuk deskripsi.
                    $kasNama = DB::table('buku_kas')->where('id', $kasId)->value('nama_buku');
                    $tujuanNama = DB::table('buku_kas')->where('id', $tujuanId)->value('nama_buku');

                    $transaksiRows[] = [
                        'user_id' => $user->id,
                        'buku_kas_id' => $kasId,
                        'dompet_id' => $dompetId,
                        'jenis_transaksi_id' => null,
                        'tanggal' => $tanggal,
                        'nominal' => $nominal,
                        'jenis' => 'Transfer Pengeluaran',
                        'transfer_code' => $transferCode,
                        'tipe_transfer' => 'buku_kas',
                        'deskripsi' => 'Transfer ke '.$tujuanNama,
                        'tujuan_buku_tabungan_id' => $tujuanId,
                        'asal_buku_tabungan_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $transaksiRows[] = [
                        'user_id' => $user->id,
                        'buku_kas_id' => $tujuanId,
                        'dompet_id' => $dompetId,
                        'jenis_transaksi_id' => null,
                        'tanggal' => $tanggal,
                        'nominal' => $nominal,
                        'jenis' => 'Transfer Pemasukan',
                        'transfer_code' => $transferCode,
                        'tipe_transfer' => 'buku_kas',
                        'deskripsi' => 'Transfer dari '.$kasNama,
                        'tujuan_buku_tabungan_id' => null,
                        'asal_buku_tabungan_id' => $kasId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $bukuSaldos[$kasIdx] -= $nominal;
                    $bukuSaldos[$tujuanIdx] += $nominal;
                } else {
                    // Buat transaksi pemasukan atau pengeluaran biasa.
                    $jenis = ($jenisRandom % 2 === 0) ? 'Pengeluaran' : 'Pemasukan';

                    // Ambil ID jenis transaksi dari data yang sudah dimuat.
                    $cacheKey = "{$user->id}_{$jenis}";
                    $jenisTransaksiId = $jenisByUser[$cacheKey]?->random()?->id;

                    $baseTanggal = DB::table('transaksi')
                        ->where('buku_kas_id', $kasId)
                        ->min('tanggal') ?? '-3 weeks';
                    $nominal = rand(1, 100);

                    $transaksiRows[] = [
                        'user_id' => $user->id,
                        'buku_kas_id' => $kasId,
                        'dompet_id' => $dompetId,
                        'jenis_transaksi_id' => $jenisTransaksiId,
                        'tanggal' => fake()->dateTimeBetween($baseTanggal, 'now'),
                        'nominal' => $nominal,
                        'jenis' => $jenis,
                        'transfer_code' => null,
                        'tipe_transfer' => null,
                        'deskripsi' => fake()->optional()->words(rand(2, 5), true),
                        'tujuan_buku_tabungan_id' => null,
                        'asal_buku_tabungan_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $bukuSaldos[$kasIdx] += ($jenis === 'Pemasukan' ? $nominal : -$nominal);
                    $saldoDompet += ($jenis === 'Pemasukan' ? $nominal : -$nominal);
                }
            }

            // Simpan seluruh transaksi tambahan pengguna secara massal.
            if (! empty($transaksiRows)) {
                DB::table('transaksi')->insert($transaksiRows);
            }

            // Perbarui saldo setiap buku kas dengan satu query per buku.
            foreach ($bukuSaldos as $bukuIdx => $saldo) {
                DB::table('buku_kas')
                    ->where('id', $bukuIdMap[$bukuIdx])
                    ->update(['saldo' => $saldo, 'updated_at' => $now]);
            }

            DB::table('dompet')->where('id', $dompetId)->update([
                'saldo' => $saldoDompet,
                'updated_at' => $now,
            ]);
        }
    }
}
