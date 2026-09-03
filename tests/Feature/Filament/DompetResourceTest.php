<?php

use App\Filament\Resources\DompetResource\Pages\ListDompet;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\Dompet;
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

test('action hapus dompet memindahkan saldo dan melakukan soft delete', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatPenggunaUntukUiDompet();
    $bank = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Bank',
        'saldo' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ListDompet::class)
        ->callTableAction('pindahkanDanHapus', $cash, data: [
            'dompet_tujuan_id' => $bank->id,
            'buku_kas_id' => $bukuKas->id,
        ])
        ->assertHasNoTableActionErrors();

    expect(Dompet::withTrashed()->findOrFail($cash->id)->trashed())->toBeTrue()
        ->and($bank->fresh()->saldo)->toBe(100000)
        ->and($bank->fresh()->is_default)->toBeTrue();
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
