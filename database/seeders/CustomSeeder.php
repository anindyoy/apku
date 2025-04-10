<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use App\Models\BukuKas;
use App\Models\ShareBuku;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Traits\TransactionSeederTrait;

class CustomSeeder extends Seeder
{
    use TransactionSeederTrait;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->createTransaksiLama();
        // $this->seederTransaction();
        // $this->hapusSetiapTransaksi();
        // $this->recalculateKas(2);

        // $transaksiPemasukan = Transaksi::factory()->create([
        //     'user_id' => 2,
        //     'buku_kas_id' => 1,
        //     'jenis' => 'Pemasukan'
        // ]);

        // // Membuat transaksi dengan jenis 'Pengeluaran'
        // $transaksiPengeluaran = Transaksi::factory()->create([
        //     'user_id' => 2,
        //     'buku_kas_id' => 1,
        //     'jenis' => 'Pengeluaran'
        // ]);

        // Transaksi::factory()
        //     ->forUser(2)
        //     ->pemasukan()
        //     ->create([
        //         'buku_kas_id' => 1,
        //     ]);

        // Transaksi::factory()
        //     ->forUser(2)
        //     ->pengeluaran()
        //     ->create([
        //         'buku_kas_id' => 1,
        //     ]);

        $this->editJenisTransaksi();
    }

    private function editJenisTransaksi()
    {
        $transaksis = Transaksi::where('user_id', 2)
            ->whereNotIn(
                'jenis_transaksi_id',
                JenisTransaksi::where('user_id', 2)->pluck('id')
            )
            ->get();

        $processedCount = 0; // Counter untuk jumlah data yang berhasil diproses

        foreach ($transaksis as $key => $value) {
            $jenisTransaksi = JenisTransaksi::where('user_id', 2)
                ->whereTipe($value->jenis)
                ->inRandomOrder()
                ->first();

            if ($jenisTransaksi) {
                $value->jenis_transaksi_id = $jenisTransaksi->id;
                $value->save();
                $processedCount++; // Tambahkan counter jika berhasil diproses
            }
        }

        // Tampilkan informasi jumlah data yang berhasil diproses
        $this->command->info("Jumlah data yang berhasil diproses: $processedCount");
    }

    private function createTransaksiLama()
    {
        $user_id = 2;
        $kas_id = 1;
        $tanggal = date('Y-m-3');

        $transaksi_terakhir = Transaksi::where('user_id', $user_id)
            ->where('buku_kas_id', $kas_id)
            ->where('tanggal', $tanggal)
            ->orderby('tanggal', 'desc')
            ->get();

        dd($transaksi_terakhir);
    }

    private function hapusSetiapTransaksi()
    {
        // hapus semua transaksi user tertentu kecuali transaksi pertama setiap buku kas
        Transaksi::where('user_id', 2)
            ->whereNotIn('id', function ($query) {
                $query->selectRaw('MIN(id)')
                    ->from('transaksi')
                    ->where('user_id', 2)
                    ->groupBy('buku_kas_id');
            })
            ->delete();
    }

    private function seederTransaction()
    {
        $userId = 2; // The ID of the user you want to seed transactions for

        // // Example 1: Seed 30 transactions starting from the 1st of the current month
        $this->seedTransactions($userId, 20, date('Y-m-1'));

        // // Example 2: Seed transactions between specific dates
        // $startDate = now()->subMonths(3);  // 3 months ago
        // $endDate = now()->subMonth();    // 1 month ago
        // $this->seedTransactions($userId, null, $startDate, $endDate);

        // // Example 3:  Seed a specific number of transactions within a date range
        // $startDate = '2024-01-01';
        // $endDate = '2024-02-29';
        // $jumlah = 50; // Seed 50 transactions
        // $this->seedTransactions($userId, $jumlah, $startDate, $endDate);

        // // Example 4:  Seed a random number of transactions (default behavior) starting from a specific date:
        // $startDate = '2024-03-15';
        // $this->seedTransactions($userId, null, $startDate); // End date will be calculated

        // // Example 5: Using Carbon objects directly
        // $startDate = \Carbon\Carbon::createFromDate(2024, 04, 01);
        // $endDate = \Carbon\Carbon::createFromDate(2024, 04, 30);
        // $this->seedTransactions($userId, null, $startDate, $endDate);

    }

    private function recalculateKas($user_id = null)
    {
        if (!$user_id) {
            $kas = BukuKas::all();
        } else {
            $kas = BukuKas::where('user_id', $user_id)->get();
        }

        foreach ($kas as $key => $value) {
            $saldo = null;
            foreach ($value->transaksi as $key => $value1) {
                if (in_array(
                    $value1->jenis,
                    ['Transfer Pemasukan', 'Pemasukan']
                )) {
                    $saldo += $value1->nominal;
                } else {
                    $saldo -= $value1->nominal;
                }
            }

            $value->saldo = $saldo;
            $value->save();
        }

        $this->command->info('Saldo Kas Recalculated.');
    }
}
