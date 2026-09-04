<?php

use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\TransaksiService;
use App\Services\TransferDompetService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
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

test('service membuat transaksi biasa dan memperbarui kedua saldo secara atomik', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatDataDompet();
    $kategori = JenisTransaksi::create([
        'user_id' => $user->id,
        'nama_jenis' => 'Gaji',
        'tipe' => 'Pemasukan',
    ]);

    $transaksi = app(TransaksiService::class)->buat($user, [
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $cash->id,
        'jenis_transaksi_id' => $kategori->id,
        'tanggal' => now(),
        'nominal' => 25000,
        'deskripsi' => 'Pendapatan layanan',
    ], 'Pemasukan');

    expect($transaksi->user_id)->toBe($user->id)
        ->and($transaksi->jenis)->toBe('Pemasukan')
        ->and($bukuKas->fresh()->saldo)->toBe(25000)
        ->and($cash->fresh()->saldo)->toBe(125000);
});

test('service transaksi biasa menolak dompet terbatas dan kategori pengguna lain', function () {
    ['user' => $user, 'bukuKas' => $bukuKas] = buatDataDompet([
        'masa_aktif' => today()->subDay(),
    ]);
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 0,
    ]);
    ['user' => $userLain] = buatDataDompet();
    $kategoriLain = JenisTransaksi::create([
        'user_id' => $userLain->id,
        'nama_jenis' => 'Rahasia',
        'tipe' => 'Pemasukan',
    ]);
    $data = [
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $dompetKetiga->id,
        'jenis_transaksi_id' => $kategoriLain->id,
        'nominal' => 10000,
    ];

    expect(fn () => app(TransaksiService::class)->buat($user, $data, 'Pemasukan'))
        ->toThrow(AuthorizationException::class);

    $data['dompet_id'] = $user->idDompetUtama();

    expect(fn () => app(TransaksiService::class)->buat($user, $data, 'Pemasukan'))
        ->toThrow(AuthorizationException::class);

    expect(Transaksi::where('user_id', $user->id)->count())->toBe(0)
        ->and($bukuKas->fresh()->saldo)->toBe(0)
        ->and($dompetKetiga->fresh()->saldo)->toBe(0);
});

test('service transfer buku kas membuat pasangan konsisten dan menjaga dompet yang sama', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatDataDompet();
    $bukuKasTujuan = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tujuan',
        'saldo' => 0,
    ]);

    $hasil = app(TransaksiService::class)->transferBukuKas(
        $user,
        $bukuKas,
        $bukuKasTujuan,
        $cash,
        $cash,
        40000,
    );

    expect($hasil['keluar']->transfer_code)->toBe($hasil['masuk']->transfer_code)
        ->and($hasil['keluar']->tipe_transfer)->toBe('buku_kas')
        ->and($bukuKas->fresh()->saldo)->toBe(-40000)
        ->and($bukuKasTujuan->fresh()->saldo)->toBe(40000)
        ->and($cash->fresh()->saldo)->toBe(100000);
});

test('service transfer buku kas menolak tujuan dompet terbatas tanpa perubahan parsial', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatDataDompet([
        'masa_aktif' => today()->subDay(),
    ]);
    $bukuKasTujuan = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tujuan',
        'saldo' => 0,
    ]);
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 0,
    ]);

    expect(fn () => app(TransaksiService::class)->transferBukuKas(
        $user,
        $bukuKas,
        $bukuKasTujuan,
        $cash,
        $dompetKetiga,
        40000,
    ))->toThrow(AuthorizationException::class);

    expect(Transaksi::where('user_id', $user->id)->count())->toBe(0)
        ->and($bukuKas->fresh()->saldo)->toBe(0)
        ->and($bukuKasTujuan->fresh()->saldo)->toBe(0)
        ->and($cash->fresh()->saldo)->toBe(100000)
        ->and($dompetKetiga->fresh()->saldo)->toBe(0);
});

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

test('saldo dapat ditransfer keluar dari dompet terbatas', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatDataDompet([
        'masa_aktif' => today()->subDay(),
    ]);
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 50000,
    ]);

    app(TransferDompetService::class)->transfer($user, $dompetKetiga, $cash, $bukuKas, 20000);

    expect($dompetKetiga->fresh()->saldo)->toBe(30000)
        ->and($cash->fresh()->saldo)->toBe(120000)
        ->and($bukuKas->fresh()->saldo)->toBe(0);
});

test('dompet ketiga otomatis terbatas setelah masa aktif kedaluwarsa', function () {
    ['user' => $user, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 0,
    ]);

    expect($user->dapatMengelolaTransaksiPadaDompet($dompetKetiga))->toBeTrue();

    $user->update(['masa_aktif' => today()->subDay()]);
    $user->refresh();

    expect($user->dapatMengelolaTransaksiPadaDompet($cash))->toBeTrue()
        ->and($user->dapatMengelolaTransaksiPadaDompet($bank))->toBeTrue()
        ->and($user->dapatMengelolaTransaksiPadaDompet($dompetKetiga))->toBeFalse();
});

test('service menolak pemindahan transaksi ke dompet terbatas', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatDataDompet([
        'masa_aktif' => today()->subDay(),
    ]);
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 0,
    ]);
    $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $cash->id,
        'tanggal' => now(),
        'nominal' => 10000,
        'jenis' => 'Pemasukan',
    ]));

    expect(fn () => app(TransaksiService::class)->ubah($user, $transaksi, [
        'dompet_id' => $dompetKetiga->id,
    ]))->toThrow(AuthorizationException::class);

    expect($transaksi->fresh()->dompet_id)->toBe($cash->id);
});

test('transaksi lama pada dompet terbatas tidak dapat diubah atau dihapus', function () {
    ['user' => $user, 'bukuKas' => $bukuKas] = buatDataDompet();
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 50000,
    ]);
    $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $dompetKetiga->id,
        'tanggal' => now(),
        'nominal' => 50000,
        'jenis' => 'Pemasukan',
    ]));
    $user->update(['masa_aktif' => today()->subDay()]);
    $user->refresh();

    expect(fn () => app(TransaksiService::class)->ubah($user, $transaksi, ['nominal' => 60000]))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(TransaksiService::class)->hapus($user, $transaksi))
        ->toThrow(AuthorizationException::class);

    expect($transaksi->fresh())->not->toBeNull()
        ->and($transaksi->fresh()->nominal)->toBe(50000)
        ->and($dompetKetiga->fresh()->saldo)->toBe(50000);
});

test('seluruh dompet otomatis dapat digunakan kembali setelah masa aktif diperpanjang', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatDataDompet([
        'masa_aktif' => today()->subDay(),
    ]);
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 0,
    ]);

    expect($user->dapatMengelolaTransaksiPadaDompet($dompetKetiga))->toBeFalse();

    $user->update(['masa_aktif' => today()->addMonth()]);
    $user->refresh();
    app(TransferDompetService::class)->transfer($user, $cash, $dompetKetiga, $bukuKas, 10000);

    expect($user->dapatMengelolaTransaksiPadaDompet($dompetKetiga))->toBeTrue()
        ->and($cash->fresh()->saldo)->toBe(90000)
        ->and($dompetKetiga->fresh()->saldo)->toBe(10000);
});

test('user super tidak terkena batas jumlah dompet miliknya', function () {
    ['user' => $user, 'cash' => $cash, 'bank' => $bank] = buatDataDompet([
        'role' => 'super',
        'masa_aktif' => null,
    ]);
    $dompetKetiga = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 0,
    ]);

    expect($user->dapatMembuatDompet())->toBeTrue()
        ->and($user->dapatMengelolaTransaksiPadaDompet($cash))->toBeTrue()
        ->and($user->dapatMengelolaTransaksiPadaDompet($bank))->toBeTrue()
        ->and($user->dapatMengelolaTransaksiPadaDompet($dompetKetiga))->toBeTrue();
});

test('user super tidak dapat mengubah saldo dompet pengguna lain', function () {
    ['user' => $super, 'bukuKas' => $bukuKasSuper, 'cash' => $cashSuper] = buatDataDompet([
        'role' => 'super',
    ]);
    ['cash' => $cashPenggunaLain] = buatDataDompet();

    expect($super->dapatMengelolaTransaksiPadaDompet($cashPenggunaLain))->toBeFalse()
        ->and(fn () => app(TransferDompetService::class)->transfer(
            $super,
            $cashSuper,
            $cashPenggunaLain,
            $bukuKasSuper,
            10000,
        ))->toThrow(AuthorizationException::class);

    expect($cashSuper->fresh()->saldo)->toBe(100000)
        ->and($cashPenggunaLain->fresh()->saldo)->toBe(100000);
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
    $bank->update(['saldo' => 50000]);

    app(TransferDompetService::class)->pindahkanSaldoDanHapus($user, $bank, $cash, $bukuKas);

    expect(Dompet::withTrashed()->findOrFail($bank->id)->trashed())->toBeTrue()
        ->and(Dompet::withTrashed()->findOrFail($bank->id)->saldo)->toBe(0)
        ->and($cash->fresh()->saldo)->toBe(150000)
        ->and($cash->fresh()->is_default)->toBeTrue();

    $this->assertDatabaseHas('transaksi', [
        'dompet_id' => $bank->id,
        'tipe_transfer' => 'dompet',
        'jenis' => 'Transfer Pengeluaran',
    ]);
});

test('hapus dompet bersaldo negatif memindahkan kewajiban ke dompet tujuan', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();
    $bank->update(['saldo' => -25000]);

    app(TransferDompetService::class)->pindahkanSaldoDanHapus($user, $bank, $cash, $bukuKas);

    expect(Dompet::withTrashed()->findOrFail($bank->id)->saldo)->toBe(0)
        ->and(Dompet::withTrashed()->findOrFail($bank->id)->trashed())->toBeTrue()
        ->and($cash->fresh()->saldo)->toBe(75000)
        ->and(Transaksi::where('user_id', $user->id)->whereNotNull('transfer_code')->count())->toBe(2);
});

test('hapus dompet bersaldo nol tidak membuat transaksi transfer', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();

    app(TransferDompetService::class)->pindahkanSaldoDanHapus($user, $bank, $cash, $bukuKas);

    expect(Dompet::withTrashed()->findOrFail($bank->id)->trashed())->toBeTrue()
        ->and(Transaksi::where('user_id', $user->id)->count())->toBe(0);
});

test('dompet default tidak dapat dihapus', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();

    expect(fn () => app(TransferDompetService::class)->pindahkanSaldoDanHapus($user, $cash, $bank, $bukuKas))
        ->toThrow(ValidationException::class);

    expect($cash->fresh()->trashed())->toBeFalse()
        ->and($cash->fresh()->saldo)->toBe(100000)
        ->and($bank->fresh()->saldo)->toBe(0);
});

test('dompet terakhir tidak dapat dihapus', function () {
    $user = User::factory()->create(['role' => 'reguler', 'masa_aktif' => today()->addMonth()]);
    $bukuKas = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Utama',
        'saldo' => 0,
        'is_default' => true,
    ]);
    $dompet = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Satu-satunya',
        'saldo' => 0,
    ]);

    expect(fn () => app(TransferDompetService::class)->pindahkanSaldoDanHapus($user, $dompet, $dompet, $bukuKas))
        ->toThrow(ValidationException::class);

    expect($dompet->fresh()->trashed())->toBeFalse();
});

test('kegagalan pemindahan saldo membatalkan penghapusan dompet', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'bank' => $bank] = buatDataDompet([
        'masa_aktif' => today()->subDay(),
    ]);
    $dompetTerbatas = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'E-Wallet',
        'saldo' => 50000,
    ]);

    expect(fn () => app(TransferDompetService::class)->pindahkanSaldoDanHapus(
        $user,
        $bank,
        $dompetTerbatas,
        $bukuKas,
    ))->toThrow(AuthorizationException::class);

    expect($bank->fresh()->trashed())->toBeFalse()
        ->and($bank->fresh()->saldo)->toBe(0)
        ->and($dompetTerbatas->fresh()->saldo)->toBe(50000)
        ->and(Transaksi::where('user_id', $user->id)->count())->toBe(0);
});

test('edit pasangan transfer memperbarui nominal dan kedua saldo secara atomik', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();
    $hasil = app(TransferDompetService::class)->transfer($user, $cash, $bank, $bukuKas, 25000);

    app(TransaksiService::class)->ubah($user, $hasil['keluar'], [
        'nominal' => 40000,
        'tanggal' => now()->subHour(),
        'deskripsi' => 'Transfer diperbarui',
    ]);

    $pasangan = Transaksi::where('transfer_code', $hasil['keluar']->transfer_code)->get();

    expect($pasangan)->toHaveCount(2)
        ->and($pasangan->pluck('nominal')->unique()->all())->toBe([40000])
        ->and($pasangan->pluck('deskripsi')->unique()->all())->toBe(['Transfer diperbarui'])
        ->and($cash->fresh()->saldo)->toBe(60000)
        ->and($bank->fresh()->saldo)->toBe(40000)
        ->and($bukuKas->fresh()->saldo)->toBe(0);
});

test('hapus salah satu sisi transfer menghapus pasangan dan mengembalikan seluruh saldo', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();
    $hasil = app(TransferDompetService::class)->transfer($user, $cash, $bank, $bukuKas, 25000);

    app(TransaksiService::class)->hapus($user, $hasil['masuk']);

    expect(Transaksi::where('transfer_code', $hasil['masuk']->transfer_code)->count())->toBe(0)
        ->and($cash->fresh()->saldo)->toBe(100000)
        ->and($bank->fresh()->saldo)->toBe(0)
        ->and($bukuKas->fresh()->saldo)->toBe(0);
});

test('edit transaksi biasa dapat memindahkan dampak saldo ke buku kas dan dompet lain', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();
    $bukuKasTujuan = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tujuan',
        'saldo' => 0,
    ]);
    $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $cash->id,
        'tanggal' => now(),
        'nominal' => 25000,
        'jenis' => 'Pemasukan',
    ]));
    $bukuKas->update(['saldo' => 25000]);
    $cash->update(['saldo' => 25000]);

    app(TransaksiService::class)->ubah($user, $transaksi, [
        'buku_kas_id' => $bukuKasTujuan->id,
        'dompet_id' => $bank->id,
        'nominal' => 40000,
    ]);

    expect($bukuKas->fresh()->saldo)->toBe(0)
        ->and($cash->fresh()->saldo)->toBe(0)
        ->and($bukuKasTujuan->fresh()->saldo)->toBe(40000)
        ->and($bank->fresh()->saldo)->toBe(40000)
        ->and($transaksi->fresh()->buku_kas_id)->toBe($bukuKasTujuan->id)
        ->and($transaksi->fresh()->dompet_id)->toBe($bank->id);
});

test('edit transaksi menolak buku kas atau dompet milik pengguna lain', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash] = buatDataDompet();
    ['bukuKas' => $bukuKasLain, 'bank' => $dompetLain] = buatDataDompet();
    $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $cash->id,
        'tanggal' => now(),
        'nominal' => 10000,
        'jenis' => 'Pemasukan',
    ]));

    expect(fn () => app(TransaksiService::class)->ubah($user, $transaksi, [
        'buku_kas_id' => $bukuKasLain->id,
        'dompet_id' => $dompetLain->id,
    ]))->toThrow(AuthorizationException::class);

    expect($transaksi->fresh()->buku_kas_id)->toBe($bukuKas->id)
        ->and($transaksi->fresh()->dompet_id)->toBe($cash->id);
});

test('filter gabungan buku kas dan dompet hanya menampilkan irisan transaksi', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'cash' => $cash, 'bank' => $bank] = buatDataDompet();
    $bukuKasKedua = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Kedua',
        'saldo' => 0,
    ]);

    $sesuai = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $cash->id,
        'tanggal' => now(),
        'nominal' => 1000,
        'jenis' => 'Pemasukan',
    ]));
    $bedaDompet = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $bank->id,
        'tanggal' => now(),
        'nominal' => 2000,
        'jenis' => 'Pemasukan',
    ]));
    $bedaBuku = Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKasKedua->id,
        'dompet_id' => $cash->id,
        'tanggal' => now(),
        'nominal' => 3000,
        'jenis' => 'Pemasukan',
    ]));

    Livewire::actingAs($user)
        ->test(ListTransaksis::class, ['filterBukuKas' => (string) $bukuKas->id])
        ->set('filterDompet', (string) $cash->id)
        ->assertCanSeeTableRecords([$sesuai])
        ->assertCanNotSeeTableRecords([$bedaDompet, $bedaBuku]);
});

test('filter dompet dari query string mengabaikan dompet pengguna lain', function () {
    ['user' => $user] = buatDataDompet();
    ['bank' => $dompetLain] = buatDataDompet();

    Livewire::withQueryParams(['filter_dompet' => $dompetLain->id])
        ->actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSet('filterDompet', null);
});
