<?php

namespace Database\Seeders;

use App\Enums\StatusLangganan;
use App\Models\Dompet;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\ShareBuku;
use App\Models\TabunganEmas;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherCode;
use App\Notifications\PengingatPerpanjanganMasaAktif;
use App\Services\TabunganEmasService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoFiturSeeder extends Seeder
{
    /**
     * Melengkapi data contoh untuk fitur yang belum dicakup seeder utama.
     */
    public function run(): void
    {
        $this->bersihkanDataDemo();

        $admin = User::admin()->firstOrFail();
        $users = User::notAdmin()->orderBy('id')->get();

        $this->buatDompetDanTransfer($users);
        $this->buatTabunganEmasDanKolaboratorKas($users);
        $this->buatDataLangganan($admin, $users);
        $this->buatNotifikasiPengingat($users);
    }

    public function bersihkanDataDemo(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('notifications')->delete();
        DB::table('transaksi_emas')->delete();
        DB::table('tabungan_emas')->delete();
        DB::table('share_buku')->delete();
        DB::table('langganans')->delete();
        DB::table('voucher_codes')->delete();
        DB::table('vouchers')->delete();
        DB::table('metode_pembayarans')->delete();
        DB::table('paket_langganans')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function buatDompetDanTransfer($users): void
    {
        foreach ($users as $user) {
            $bukuKas = $user->buku_kas()->orderByDesc('is_default')->firstOrFail();
            $dompetUtama = Dompet::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('is_default', true)
                ->firstOrFail();
            $dompetBank = Dompet::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'nama_dompet' => 'Rekening Bank',
                'saldo' => 0,
                'is_default' => false,
                'description' => 'Rekening untuk tabungan dan pembayaran digital',
            ]);
            $waktu = now()->subDays(5);

            Transaksi::withoutEvents(function () use ($user, $bukuKas, $dompetUtama, $dompetBank, $waktu): void {
                Transaksi::create([
                    'user_id' => $user->id,
                    'buku_kas_id' => $bukuKas->id,
                    'dompet_id' => $dompetBank->id,
                    'tanggal' => $waktu->copy()->subDay(),
                    'nominal' => 100000,
                    'jenis' => 'Pemasukan',
                    'deskripsi' => 'Saldo awal rekening bank',
                ]);

                $kodeTransfer = (string) Str::uuid();
                $dataTransfer = [
                    'user_id' => $user->id,
                    'buku_kas_id' => $bukuKas->id,
                    'tanggal' => $waktu,
                    'nominal' => 100000,
                    'transfer_code' => $kodeTransfer,
                    'tipe_transfer' => 'dompet',
                ];

                Transaksi::create($dataTransfer + [
                    'dompet_id' => $dompetBank->id,
                    'jenis' => 'Transfer Pengeluaran',
                    'deskripsi' => 'Transfer ke dompet tunai',
                ]);
                Transaksi::create($dataTransfer + [
                    'dompet_id' => $dompetUtama->id,
                    'jenis' => 'Transfer Pemasukan',
                    'deskripsi' => 'Transfer dari rekening bank',
                ]);
            });

            $dompetUtama->increment('saldo', 100000);
            $bukuKas->increment('saldo', 100000);
        }
    }

    private function buatTabunganEmasDanKolaboratorKas($users): void
    {
        foreach ($users as $index => $user) {
            $bukuKas = $user->buku_kas()->orderByDesc('is_default')->firstOrFail();

            for ($nomor = 0; $nomor < 1 + $index % 2; $nomor++) {
                $tabungan = TabunganEmas::create([
                    'buku_kas_id' => $bukuKas->id,
                    'label' => $nomor === 0 ? 'Emas Antam' : 'Emas UBS',
                    'berat_gram' => 0,
                    'total_modal' => 0,
                    'harga_beli' => $nomor === 0 ? 1500000 : 750000,
                    'keterangan' => 'Contoh tabungan emas logam mulia.',
                    'created_at' => now()->subDays(10 + $nomor),
                ]);

                app(TabunganEmasService::class)->catatSaldoAwal(
                    $user,
                    $tabungan,
                    $nomor === 0 ? 1 : 0.5,
                    0,
                    'Saldo awal emas contoh.',
                );
            }

            for ($nomor = 1; $nomor <= min(2, $users->count() - 1); $nomor++) {
                $kolaborator = $users[($index + $nomor) % $users->count()];

                ShareBuku::create([
                    'buku_kas_id' => $bukuKas->id,
                    'user_id' => $kolaborator->id,
                    'invited_by_user_id' => $user->id,
                    'privilege' => $nomor === 1 ? 'viewer' : 'editor',
                    'berlaku_mulai' => now()->subDay(),
                    'berlaku_sampai' => null,
                ]);
            }
        }
    }

    private function buatDataLangganan(User $admin, $users): void
    {
        $bulanan = PaketLangganan::create([
            'label' => 'Premium 30 Hari',
            'harga' => 25000,
            'durasi_hari' => 30,
            'is_active' => true,
        ]);
        $tahunan = PaketLangganan::create([
            'label' => 'Premium 1 Tahun',
            'harga' => 250000,
            'durasi_hari' => 365,
            'is_active' => true,
        ]);
        PaketLangganan::create([
            'label' => 'Premium 90 Hari (Arsip)',
            'harga' => 70000,
            'durasi_hari' => 90,
            'is_active' => false,
        ]);

        $bank = MetodePembayaran::create([
            'label' => 'Transfer Bank BCA',
            'jenis' => 'bank',
            'nama_penyedia' => 'BCA',
            'nomor_tujuan' => '1234567890',
            'nama_pemilik' => 'APKu Indonesia',
            'instruksi' => 'Transfer sesuai total pembayaran dan unggah bukti pembayaran.',
            'is_active' => true,
            'urutan' => 1,
        ]);
        $ewallet = MetodePembayaran::create([
            'label' => 'Dompet Digital',
            'jenis' => 'e_wallet',
            'nama_penyedia' => 'GoPay',
            'nomor_tujuan' => '081234567890',
            'nama_pemilik' => 'APKu Indonesia',
            'instruksi' => 'Pastikan nama penerima sesuai sebelum melakukan pembayaran.',
            'is_active' => true,
            'urutan' => 2,
        ]);
        MetodePembayaran::create([
            'label' => 'QRIS (Arsip)',
            'jenis' => 'qr',
            'nama_penyedia' => 'QRIS',
            'instruksi' => 'Pindai kode QR dan bayar sesuai total order.',
            'gambar_qr_path' => 'metode-pembayaran/demo-qris.png',
            'is_active' => false,
            'urutan' => 3,
        ]);

        $voucherBerulang = Voucher::create([
            'label' => 'Promo Pengguna Baru',
            'masa_aktif' => today()->addMonths(3),
            'jumlah_diskon' => 20,
            'dapat_dipakai_berulang' => true,
        ]);
        $kodeBerulang = VoucherCode::create(['voucher_id' => $voucherBerulang->id, 'code' => 'HEMAT20']);
        $voucherSekali = Voucher::create([
            'label' => 'Promo Tahunan',
            'masa_aktif' => today()->addMonth(),
            'jumlah_diskon' => 10,
            'dapat_dipakai_berulang' => false,
        ]);
        VoucherCode::create(['voucher_id' => $voucherSekali->id, 'code' => 'TAHUNAN10']);

        $statuses = StatusLangganan::cases();

        foreach ($statuses as $index => $status) {
            $user = $users[$index % $users->count()];
            $paket = $index % 2 === 0 ? $bulanan : $tahunan;
            $metode = $index % 2 === 0 ? $bank : $ewallet;
            $diskon = $index === 0 ? (int) round($paket->harga * 0.2) : 0;
            $sudahDikonfirmasi = in_array($status, [StatusLangganan::MenungguVerifikasi, StatusLangganan::Disetujui, StatusLangganan::Ditolak], true);
            $sudahDiverifikasi = in_array($status, [StatusLangganan::Disetujui, StatusLangganan::Ditolak], true);

            Langganan::create([
                'kode_order' => sprintf('DEMO-%s-%02d', now()->format('Ym'), $index + 1),
                'user_id' => $user->id,
                'paket_langganan_id' => $paket->id,
                'metode_pembayaran_id' => $metode->id,
                'voucher_code_id' => $index === 0 ? $kodeBerulang->id : null,
                'label_paket' => $paket->label,
                'harga' => $paket->harga,
                'durasi_hari' => $paket->durasi_hari,
                'label_metode_pembayaran' => $metode->label,
                'detail_pembayaran' => [
                    'jenis' => $metode->jenis,
                    'nama_penyedia' => $metode->nama_penyedia,
                    'nomor_tujuan' => $metode->nomor_tujuan,
                    'nama_pemilik' => $metode->nama_pemilik,
                    'instruksi' => $metode->instruksi,
                ],
                'kode_voucher' => $index === 0 ? $kodeBerulang->code : null,
                'persentase_diskon' => $index === 0 ? 20 : 0,
                'nominal_diskon' => $diskon,
                'total_pembayaran' => $paket->harga - $diskon,
                'status' => $status,
                'bukti_pembayaran_path' => $sudahDikonfirmasi ? 'bukti-pembayaran/demo-transfer.jpg' : null,
                'tanggal_konfirmasi' => $sudahDikonfirmasi ? now()->subDays(3) : null,
                'catatan_user' => 'Data contoh alur langganan.',
                'catatan_admin' => $status === StatusLangganan::Ditolak ? 'Nominal pada bukti belum sesuai.' : null,
                'diverifikasi_oleh' => $sudahDiverifikasi ? $admin->id : null,
                'tanggal_verifikasi' => $sudahDiverifikasi ? now()->subDays(2) : null,
                'masa_aktif_mulai' => $status === StatusLangganan::Disetujui ? today()->subDays(2) : null,
                'masa_aktif_sampai' => $status === StatusLangganan::Disetujui ? today()->addDays($paket->durasi_hari - 3) : null,
                'created_at' => now()->subDays(5 - $index),
                'updated_at' => now(),
            ]);
        }
    }

    private function buatNotifikasiPengingat($users): void
    {
        $user = $users->firstWhere('email', 'premium@apku.com') ?? $users->first();
        $tanggalBerakhir = Carbon::today()->addDays(7);

        $user->forceFill([
            'type' => 'premium',
            'masa_aktif' => $tanggalBerakhir,
        ])->save();
        $user->notify(new PengingatPerpanjanganMasaAktif(7, $tanggalBerakhir->format('Y-m-d')));
    }
}
