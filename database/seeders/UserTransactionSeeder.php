<?php

namespace Database\Seeders;

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeder khusus untuk membuat transaksi 3 bulan terakhir untuk user tertentu.
 *
 * Penggunaan:
 *   php artisan db:seed --class=UserTransactionSeeder
 *   php artisan db:seed --class=UserTransactionSeeder --email=mursita77@example.org
 *   php artisan db:seed --class=UserTransactionSeeder --email=mursita77@example.org --months=6
 *
 * Atau via artisan tinker:
 *   (new \Database\Seeders\UserTransactionSeeder)->setEmail('mursita77@example.org')->run();
 */
class UserTransactionSeeder extends Seeder
{
    private ?string $email = null;

    private int $months = 3;

    /**
     * Tampilkan baris melalui output command atau stdout.
     */
    private function line(string $message, string $type = 'info'): void
    {
        if ($this->command) {
            match ($type) {
                'error' => $this->command->error($message),
                default => $this->command->info($message),
            };
        } else {
            echo $message.PHP_EOL;
        }

    }

    /**
     * Tentukan email pengguna tujuan.
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Tentukan jumlah bulan ke belakang.
     */
    public function setMonths(int $months): static
    {
        $this->months = $months;

        return $this;
    }

    /**
     * Jalankan seeder database.
     *
     * Penggunaan melalui artisan tinker:
     *   (new \Database\Seeders\UserTransactionSeeder)
     *       ->setEmail('mursita77@example.org')
     *       ->setMonths(3)
     *       ->run();
     *
     * Penggunaan satu baris melalui artisan tinker:
     *   php artisan tinker --execute="(new \Database\Seeders\UserTransactionSeeder)->setEmail('mursita77@example.org')->setMonths(3)->run();"
     */
    public function run(): void
    {

        $user = User::where('email', $this->email)->first();

        if (! $this->email) {
            $this->line('Email belum ditentukan! Gunakan setEmail() atau --email.', 'error');

            return;
        }

        if (! $user) {
            $this->line("User dengan email {$this->email} tidak ditemukan!", 'error');

            return;
        }

        $this->line("User: {$user->name} (ID: {$user->id})");

        // --- 1. Pastikan user punya BukuKas ---
        $bukuKas = BukuKas::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->first();

        if (! $bukuKas) {
            $bukuKas = BukuKas::create([
                'user_id' => $user->id,
                'nama_buku' => 'Kas Utama',
                'saldo' => 0,
                'is_default' => true,
                'description' => 'Buku kas utama',
            ]);
            $this->line("  Created BukuKas: {$bukuKas->nama_buku}");
        } else {
            $this->line("  Using BukuKas: {$bukuKas->nama_buku} (ID: {$bukuKas->id})");
        }

        $dompet = Dompet::withoutGlobalScopes()->firstOrCreate(
            ['user_id' => $user->id, 'nama_dompet' => 'Cash'],
            ['saldo' => 0, 'is_default' => true, 'description' => 'Dompet tunai utama']
        );

        // --- 2. Pastikan user punya JenisTransaksi ---
        $pemasukanType = JenisTransaksi::where('user_id', $user->id)
            ->where('tipe', 'Pemasukan')
            ->first();

        if (! $pemasukanType) {
            $pemasukanType = JenisTransaksi::create([
                'user_id' => $user->id,
                'nama_jenis' => 'Gaji',
                'tipe' => 'Pemasukan',
            ]);
            $this->line('  Created JenisTransaksi Pemasukan: Gaji');
        }

        $pengeluaranType = JenisTransaksi::where('user_id', $user->id)
            ->where('tipe', 'Pengeluaran')
            ->first();

        if (! $pengeluaranType) {
            $pengeluaranType = JenisTransaksi::create([
                'user_id' => $user->id,
                'nama_jenis' => 'Makanan',
                'tipe' => 'Pengeluaran',
            ]);
            $this->line('  Created JenisTransaksi Pengeluaran: Makanan');
        }

        // Tahap 3: buat transaksi untuk beberapa bulan terakhir.
        $now = Carbon::now();
        $allTransactions = [];

        $pemasukanNominals = [500000, 750000, 1000000, 1500000, 2000000, 2500000, 3000000];
        $pemasukanDescriptions = [
            'Gaji bulanan',
            'Bonus project',
            'Freelance',
            'Penjualan',
            'Pemasukan lainnya',
        ];

        $pengeluaranNominals = [15000, 25000, 35000, 50000, 75000, 100000, 150000, 200000, 250000, 500000];
        $pengeluaranDescriptions = [
            'Makan siang',
            'Belanja groceries',
            'Transport',
            'Tagihan listrik',
            'Internet',
            'Pulsa',
            'Hiburan',
            'Kebutuhan rumah',
            'Makan malam',
            'Coffee',
        ];

        for ($m = $this->months - 1; $m >= 0; $m--) {
            $month = $now->copy()->subMonths($m);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            // 3-5 pemasukan per bulan
            $incomeCount = rand(3, 5);
            for ($i = 0; $i < $incomeCount; $i++) {
                $day = rand(1, $monthEnd->day);
                $hour = rand(6, 20);
                $minute = rand(0, 59);
                $date = $monthStart->copy()->day($day)->hour($hour)->minute($minute);

                $allTransactions[] = [
                    'buku_kas_id' => $bukuKas->id,
                    'dompet_id' => $dompet->id,
                    'user_id' => $user->id,
                    'jenis_transaksi_id' => $pemasukanType->id,
                    'tanggal' => $date->format('Y-m-d H:i:s'),
                    'nominal' => $pemasukanNominals[array_rand($pemasukanNominals)],
                    'jenis' => 'Pemasukan',
                    'transfer_code' => null,
                    'tipe_transfer' => null,
                    'deskripsi' => $pemasukanDescriptions[array_rand($pemasukanDescriptions)],
                    'tujuan_buku_tabungan_id' => null,
                    'asal_buku_tabungan_id' => null,
                    'created_at' => $now->format('Y-m-d H:i:s'),
                    'updated_at' => $now->format('Y-m-d H:i:s'),
                ];
            }

            // 5-10 pengeluaran per bulan
            $expenseCount = rand(5, 10);
            for ($i = 0; $i < $expenseCount; $i++) {
                $day = rand(1, $monthEnd->day);
                $hour = rand(6, 22);
                $minute = rand(0, 59);
                $date = $monthStart->copy()->day($day)->hour($hour)->minute($minute);

                $allTransactions[] = [
                    'buku_kas_id' => $bukuKas->id,
                    'dompet_id' => $dompet->id,
                    'user_id' => $user->id,
                    'jenis_transaksi_id' => $pengeluaranType->id,
                    'tanggal' => $date->format('Y-m-d H:i:s'),
                    'nominal' => $pengeluaranNominals[array_rand($pengeluaranNominals)],
                    'jenis' => 'Pengeluaran',
                    'transfer_code' => null,
                    'tipe_transfer' => null,
                    'deskripsi' => $pengeluaranDescriptions[array_rand($pengeluaranDescriptions)],
                    'tujuan_buku_tabungan_id' => null,
                    'asal_buku_tabungan_id' => null,
                    'created_at' => $now->format('Y-m-d H:i:s'),
                    'updated_at' => $now->format('Y-m-d H:i:s'),
                ];
            }

            $this->line("  Generated transactions for {$month->format('F Y')}");
        }

        // Urutkan berdasarkan tanggal.
        usort($allTransactions, fn ($a, $b) => strtotime($a['tanggal']) - strtotime($b['tanggal']));

        // Simpan massal dalam beberapa bagian.
        $chunks = array_chunk($allTransactions, 50);
        $totalInserted = 0;

        foreach ($chunks as $chunk) {
            DB::table('transaksi')->insert($chunk);
            $totalInserted += count($chunk);
        }

        // Tahap 4: perbarui saldo buku kas.
        $totalPemasukan = DB::table('transaksi')
            ->where('user_id', $user->id)
            ->where('jenis', 'Pemasukan')
            ->sum('nominal');

        $totalPengeluaran = DB::table('transaksi')
            ->where('user_id', $user->id)
            ->where('jenis', 'Pengeluaran')
            ->sum('nominal');

        DB::table('buku_kas')
            ->where('id', $bukuKas->id)
            ->update([
                'saldo' => $totalPemasukan - $totalPengeluaran,
                'updated_at' => $now,
            ]);

        DB::table('dompet')->where('id', $dompet->id)->update([
            'saldo' => $totalPemasukan - $totalPengeluaran,
            'updated_at' => $now,
        ]);

        // Tahap 5: tampilkan ringkasan.
        $this->line('');
        $this->line('=== SUMMARY ===');
        $this->line("Total transaksi dibuat: {$totalInserted}");

        $pemasukanCount = DB::table('transaksi')->where('user_id', $user->id)->where('jenis', 'Pemasukan')->count();
        $pengeluaranCount = DB::table('transaksi')->where('user_id', $user->id)->where('jenis', 'Pengeluaran')->count();
        $this->line("Pemasukan: {$pemasukanCount} transaksi (Rp ".number_format($totalPemasukan, 0, ',', '.').')');
        $this->line("Pengeluaran: {$pengeluaranCount} transaksi (Rp ".number_format($totalPengeluaran, 0, ',', '.').')');
        $this->line("Saldo BukuKas {$bukuKas->nama_buku}: Rp ".number_format($totalPemasukan - $totalPengeluaran, 0, ',', '.'));

        $this->line('');
        $this->line('=== PER MONTH ===');

        for ($m = $this->months - 1; $m >= 0; $m--) {
            $month = $now->copy()->subMonths($m);
            $monthPemasukan = DB::table('transaksi')
                ->where('user_id', $user->id)
                ->where('jenis', 'Pemasukan')
                ->whereYear('tanggal', $month->year)
                ->whereMonth('tanggal', $month->month)
                ->sum('nominal');

            $monthPengeluaran = DB::table('transaksi')
                ->where('user_id', $user->id)
                ->where('jenis', 'Pengeluaran')
                ->whereYear('tanggal', $month->year)
                ->whereMonth('tanggal', $month->month)
                ->sum('nominal');

            $monthCount = DB::table('transaksi')
                ->where('user_id', $user->id)
                ->whereYear('tanggal', $month->year)
                ->whereMonth('tanggal', $month->month)
                ->count();

            $this->line(
                "{$month->format('F Y')}: {$monthCount} transaksi ".
                '(Pemasukan: Rp '.number_format($monthPemasukan, 0, ',', '.').
                ', Pengeluaran: Rp '.number_format($monthPengeluaran, 0, ',', '.').')'
            );
        }
    }
}
