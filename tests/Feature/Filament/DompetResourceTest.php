<?php

use App\Filament\Resources\DompetResource\Pages\ListDompet;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use Livewire\Livewire;

function buatPenggunaUntukUiDompet(?string $masaAktif = null): array
{
    $user = User::factory()->create([
        'role' => 'reguler',
        'masa_aktif' => $masaAktif ?? today()->addMonth(),
    ]);
    $bukuKas = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Utama',
        'saldo' => 0,
        'is_default' => true,
    ]);
    $cash = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Cash',
        'saldo' => 100000,
        'is_default' => true,
    ]);

    return compact('user', 'bukuKas', 'cash');
}

test('halaman dompet dapat membuat dan mengubah dompet', function () {
    ['user' => $user] = buatPenggunaUntukUiDompet();

    Livewire::actingAs($user)
        ->test(ListDompet::class)
        ->assertSuccessful()
        ->callAction('create', data: [
            'nama_dompet' => 'Bank',
            'saldo' => 0,
            'description' => 'Rekening utama',
        ])
        ->assertHasNoActionErrors();

    $bank = $user->dompet()->where('nama_dompet', 'Bank')->firstOrFail();

    Livewire::actingAs($user)
        ->test(ListDompet::class)
        ->callTableAction('edit', $bank, data: [
            'nama_dompet' => 'Bank Bisnis',
            'description' => 'Rekening bisnis',
        ])
        ->assertHasNoTableActionErrors();

    expect($bank->fresh()->nama_dompet)->toBe('Bank Bisnis');
});

test('action transaksi biasa menggunakan service untuk memperbarui saldo', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatPenggunaUntukUiDompet();
    $kategoriMasuk = JenisTransaksi::create([
        'user_id' => $user->id,
        'nama_jenis' => 'Gaji',
        'tipe' => 'Pemasukan',
    ]);
    $kategoriKeluar = JenisTransaksi::create([
        'user_id' => $user->id,
        'nama_jenis' => 'Belanja',
        'tipe' => 'Pengeluaran',
    ]);
    $komponen = Livewire::actingAs($user)
        ->test(ListTransaksis::class, ['filterBukuKas' => (string) $bukuKas->id]);

    $komponen->callAction('Catat Pemasukan', data: [
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $cash->id,
        'jenis_transaksi_id' => $kategoriMasuk->id,
        'tanggal' => now(),
        'nominal' => 25000,
        'deskripsi' => 'Pemasukan lewat service',
    ])->assertHasNoActionErrors();
    $komponen->callAction('Catat Pengeluaran', data: [
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $cash->id,
        'jenis_transaksi_id' => $kategoriKeluar->id,
        'tanggal' => now(),
        'nominal' => 10000,
        'deskripsi' => 'Pengeluaran lewat service',
    ])->assertHasNoActionErrors();

    expect($bukuKas->fresh()->saldo)->toBe(15000)
        ->and($cash->fresh()->saldo)->toBe(115000)
        ->and(Transaksi::where('user_id', $user->id)->count())->toBe(2);
});

test('pengguna tanpa masa aktif tidak dapat membuat dompet ketiga', function () {
    ['user' => $user] = buatPenggunaUntukUiDompet(today()->subDay()->toDateString());
    Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Bank',
        'saldo' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ListDompet::class)
        ->assertActionHidden('create');
});

test('user super dapat membuat dompet tanpa mengelola dompet pengguna lain', function () {
    $super = User::factory()->create(['role' => 'super', 'masa_aktif' => null]);
    Dompet::create(['user_id' => $super->id, 'nama_dompet' => 'Cash', 'saldo' => 0, 'is_default' => true]);
    Dompet::create(['user_id' => $super->id, 'nama_dompet' => 'Bank', 'saldo' => 0]);
    Dompet::create(['user_id' => $super->id, 'nama_dompet' => 'E-Wallet', 'saldo' => 0]);
    ['cash' => $dompetPenggunaLain] = buatPenggunaUntukUiDompet();

    Livewire::actingAs($super)
        ->test(ListDompet::class)
        ->set('tableRecordsPerPage', 50)
        ->assertActionVisible('create')
        ->assertCanSeeTableRecords([$dompetPenggunaLain])
        ->assertTableActionHidden('edit', $dompetPenggunaLain)
        ->assertTableActionHidden('pindahkanDanHapus', $dompetPenggunaLain);
});

test('dompet dan transaksi ketiga tetap terlihat tetapi action pengelolaan disembunyikan', function () {
    ['user' => $user, 'bukuKas' => $bukuKas] = buatPenggunaUntukUiDompet();
    Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Bank', 'saldo' => 0]);
    $dompetKetiga = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'E-Wallet', 'saldo' => 50000]);
    $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $dompetKetiga->id,
        'tanggal' => now(),
        'nominal' => 50000,
        'jenis' => 'Pemasukan',
    ]));
    $user->update(['masa_aktif' => today()->subDay()]);

    Livewire::actingAs($user->refresh())
        ->test(ListDompet::class)
        ->assertCanSeeTableRecords([$dompetKetiga])
        ->assertTableColumnStateSet('status_akses', 'Terbatas', $dompetKetiga)
        ->assertTableActionHidden('edit', $dompetKetiga)
        ->assertTableActionHidden('pindahkanDanHapus', $dompetKetiga);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertCanSeeTableRecords([$transaksi])
        ->assertTableActionHidden('edit', $transaksi)
        ->assertTableActionHidden('delete', $transaksi);
});

test('action transfer dompet tersedia tanpa masa aktif dan menghasilkan saldo negatif', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatPenggunaUntukUiDompet(today()->subDay()->toDateString());
    $bank = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Bank',
        'saldo' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertActionVisible('Pindah saldo dompet')
        ->callAction('Pindah saldo dompet', data: [
            'dompet_asal_id' => $cash->id,
            'dompet_tujuan_id' => $bank->id,
            'buku_kas_id' => $bukuKas->id,
            'tanggal' => now(),
            'nominal' => 125000,
            'deskripsi' => 'Transfer melalui UI',
        ])
        ->assertHasNoActionErrors();

    expect($cash->fresh()->saldo)->toBe(-25000)
        ->and($bank->fresh()->saldo)->toBe(125000);
});

test('action transfer mengizinkan saldo keluar dari dompet terbatas', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatPenggunaUntukUiDompet(today()->subDay()->toDateString());
    Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Bank', 'saldo' => 0]);
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 50000,
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertActionVisible('Pindah saldo dompet')
        ->callAction('Pindah saldo dompet', data: [
            'dompet_asal_id' => $dompetKetiga->id,
            'dompet_tujuan_id' => $cash->id,
            'buku_kas_id' => $bukuKas->id,
            'tanggal' => now(),
            'nominal' => 20000,
            'deskripsi' => 'Keluarkan saldo dompet terbatas',
        ])
        ->assertHasNoActionErrors();

    expect($dompetKetiga->fresh()->saldo)->toBe(30000)
        ->and($cash->fresh()->saldo)->toBe(120000);
});

test('action hapus dompet memindahkan saldo dan melakukan soft delete', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatPenggunaUntukUiDompet();
    $bank = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Bank',
        'saldo' => 50000,
    ]);

    Livewire::actingAs($user)
        ->test(ListDompet::class)
        ->assertTableActionHidden('pindahkanDanHapus', $cash)
        ->callTableAction('pindahkanDanHapus', $bank, data: [
            'dompet_tujuan_id' => $cash->id,
            'buku_kas_id' => $bukuKas->id,
        ])
        ->assertHasNoTableActionErrors();

    expect(Dompet::withTrashed()->findOrFail($bank->id)->trashed())->toBeTrue()
        ->and($cash->fresh()->saldo)->toBe(150000)
        ->and($cash->fresh()->is_default)->toBeTrue();
});

test('transfer buku kas dengan dompet sama menjaga saldo bersih dompet', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatPenggunaUntukUiDompet();
    $bukuKasTujuan = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tujuan',
        'saldo' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class, ['filterBukuKas' => (string) $bukuKas->id])
        ->callAction('Transfer saldo', data: [
            'buku_kas_id' => $bukuKas->id,
            'buku_kas_id_tujuan' => $bukuKasTujuan->id,
            'dompet_id' => $cash->id,
            'dompet_id_tujuan' => $cash->id,
            'tanggal' => now(),
            'nominal' => 25000,
            'deskripsi' => 'Transfer buku dengan dompet sama',
        ])
        ->assertHasNoActionErrors();

    expect($bukuKas->fresh()->saldo)->toBe(-25000)
        ->and($bukuKasTujuan->fresh()->saldo)->toBe(25000)
        ->and($cash->fresh()->saldo)->toBe(100000);
});

test('transfer buku kas dengan dompet berbeda turut memindahkan saldo dompet', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatPenggunaUntukUiDompet();
    $bukuKasTujuan = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tujuan',
        'saldo' => 0,
    ]);
    $bank = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Bank',
        'saldo' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class, ['filterBukuKas' => (string) $bukuKas->id])
        ->callAction('Transfer saldo', data: [
            'buku_kas_id' => $bukuKas->id,
            'buku_kas_id_tujuan' => $bukuKasTujuan->id,
            'dompet_id' => $cash->id,
            'dompet_id_tujuan' => $bank->id,
            'tanggal' => now(),
            'nominal' => 25000,
            'deskripsi' => 'Transfer buku dengan dompet berbeda',
        ])
        ->assertHasNoActionErrors();

    expect($bukuKas->fresh()->saldo)->toBe(-25000)
        ->and($bukuKasTujuan->fresh()->saldo)->toBe(25000)
        ->and($cash->fresh()->saldo)->toBe(75000)
        ->and($bank->fresh()->saldo)->toBe(25000);
});
