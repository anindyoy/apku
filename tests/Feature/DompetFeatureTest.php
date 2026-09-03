<?php

use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\TransferDompetService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

function buatDataDompet(array $atributUser = []): array
{
    $user = User::factory()->create(array_merge([
        'role' => 'reguler',
        'masa_aktif' => today()->addMonth(),
    ], $atributUser));
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
    $bank = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Bank',
        'saldo' => 0,
    ]);

    return compact('user', 'bukuKas', 'cash', 'bank');
}

test('transfer dompet memindahkan saldo tanpa mengubah saldo buku kas', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();

    $hasil = app(TransferDompetService::class)->transfer(
        $user,
        $cash,
        $bank,
        $bukuKas,
        125000,
        now(),
        'Pindah ke bank',
    );

    expect($cash->fresh()->saldo)->toBe(-25000)
        ->and($bank->fresh()->saldo)->toBe(125000)
        ->and($bukuKas->fresh()->saldo)->toBe(0)
        ->and($hasil['keluar']->transfer_code)->toBe($hasil['masuk']->transfer_code)
        ->and($hasil['keluar']->tipe_transfer)->toBe('dompet')
        ->and($hasil['masuk']->tipe_transfer)->toBe('dompet');
});

test('pengguna tanpa masa aktif tetap dapat transfer pada dua dompet gratis', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet([
        'masa_aktif' => today()->subDay(),
    ]);

    app(TransferDompetService::class)->transfer($user, $cash, $bank, $bukuKas, 10000);

    expect($cash->fresh()->saldo)->toBe(90000)
        ->and($bank->fresh()->saldo)->toBe(10000);
});

test('dompet ketiga tidak dapat dipakai ketika masa aktif tidak valid', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet([
        'masa_aktif' => today()->subDay(),
    ]);
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 0,
    ]);

    expect(fn () => app(TransferDompetService::class)->transfer($user, $cash, $dompetKetiga, $bukuKas, 10000))
        ->toThrow(AuthorizationException::class);

    expect($bank->fresh()->saldo)->toBe(0)
        ->and($dompetKetiga->fresh()->saldo)->toBe(0);
});

test('filter dompet hanya menampilkan transaksi dompet terpilih', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();

    $transaksiCash = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $cash->id,
        'tanggal' => now(),
        'nominal' => 1000,
        'jenis' => 'Pemasukan',
    ]));
    $transaksiBank = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $bank->id,
        'tanggal' => now(),
        'nominal' => 2000,
        'jenis' => 'Pemasukan',
    ]));

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->set('filterDompet', (string) $cash->id)
        ->assertCanSeeTableRecords([$transaksiCash])
        ->assertCanNotSeeTableRecords([$transaksiBank]);
});

test('hapus dompet memindahkan saldo lalu mempertahankan histori dengan soft delete', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();

    app(TransferDompetService::class)->pindahkanSaldoDanHapus($user, $cash, $bank, $bukuKas);

    expect(Dompet::withTrashed()->findOrFail($cash->id)->trashed())->toBeTrue()
        ->and(Dompet::withTrashed()->findOrFail($cash->id)->saldo)->toBe(0)
        ->and($bank->fresh()->saldo)->toBe(100000)
        ->and($bank->fresh()->is_default)->toBeTrue();

    $this->assertDatabaseHas('transaksi', [
        'dompet_id' => $cash->id,
        'tipe_transfer' => 'dompet',
        'jenis' => 'Transfer Pengeluaran',
    ]);
});
