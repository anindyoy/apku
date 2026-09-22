<?php

use App\Filament\Resources\TransaksiResource;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\TransaksiService;

test('saldo riwayat tetap tepat setelah transaksi tengah dihapus dan daftar difilter', function () {
    $user = User::factory()->create(['role' => 'reguler', 'masa_aktif' => today()->addMonth()]);
    $kas = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas', 'saldo' => 0, 'is_default' => true]);
    $dompet = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Tunai', 'saldo' => 0, 'is_default' => true]);
    $kategori = JenisTransaksi::create(['user_id' => $user->id, 'nama_jenis' => 'Gaji', 'tipe' => 'Pemasukan']);
    $service = app(TransaksiService::class);
    $data = ['buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id, 'jenis_transaksi_id' => $kategori->id];

    $awal = $service->buat($user, $data + ['tanggal' => now()->subDays(3), 'nominal' => 100000], 'Pemasukan');
    $tengah = $service->buat($user, $data + ['tanggal' => now()->subDays(2), 'nominal' => 30000], 'Pemasukan');
    $akhir = $service->buat($user, $data + ['tanggal' => now()->subDay(), 'nominal' => 20000], 'Pemasukan');

    $service->hapus($user, $tengah);

    $riwayat = TransaksiResource::queryDenganSaldo(Transaksi::query())
        ->whereKey($awal->id)
        ->firstOrFail();
    $terbaru = TransaksiResource::queryDenganSaldo(Transaksi::query())
        ->whereKey($akhir->id)
        ->firstOrFail();

    expect((int) $riwayat->saldo)->toBe(100000)
        ->and((int) $riwayat->saldo_dompet)->toBe(100000)
        ->and((int) $terbaru->saldo)->toBe(120000)
        ->and((int) $terbaru->saldo_dompet)->toBe(120000)
        ->and((int) $kas->fresh()->saldo)->toBe(120000)
        ->and((int) $dompet->fresh()->saldo)->toBe(120000);
});
