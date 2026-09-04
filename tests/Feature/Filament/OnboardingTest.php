<?php

use App\Filament\Pages\Onboarding;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\User;
use App\Services\PastikanAkunKeuanganDefault;
use Livewire\Livewire;

test('pengguna baru diarahkan ke halaman onboarding', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect(Onboarding::getUrl());
});

test('pengguna baru dapat menyimpan pengaturan awal', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->fillForm([
            'nama_buku' => 'Rekening Usaha',
            'description' => 'Kas operasional toko',
            'saldo_awal' => 150000,
            'kategori_pemasukan' => [
                ['nama_jenis' => 'Penjualan'],
                ['nama_jenis' => 'Modal'],
            ],
            'kategori_pengeluaran' => [
                ['nama_jenis' => 'Belanja Stok'],
                ['nama_jenis' => 'Transportasi'],
            ],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $bukuKas = $user->buku_kas()->first();

    expect($bukuKas)
        ->nama_buku->toBe('Rekening Usaha')
        ->description->toBe('Kas operasional toko')
        ->saldo->toBe(150000);

    $dompet = $user->dompet()->firstOrFail();

    expect($dompet)
        ->nama_dompet->toBe('Cash')
        ->saldo->toBe(150000)
        ->is_default->toBeTrue();

    $this->assertDatabaseHas('transaksi', [
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $dompet->id,
        'nominal' => 150000,
        'jenis' => 'Pemasukan',
        'deskripsi' => 'Saldo awal',
    ]);
    $this->assertDatabaseHas('jenis_transaksi', ['user_id' => $user->id, 'tipe' => 'Pemasukan', 'nama_jenis' => 'Penjualan']);
    $this->assertDatabaseHas('jenis_transaksi', ['user_id' => $user->id, 'tipe' => 'Pengeluaran', 'nama_jenis' => 'Belanja Stok']);
});

test('label default onboarding terisi tanpa mengisi kategori dengan placeholder', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->assertSet('data.nama_buku', 'Kas Utama')
        ->assertSet('data.nama_dompet', 'Cash')
        ->assertSet('data.kategori_pemasukan', fn (array $items) => collect($items)->pluck('nama_jenis')->filter()->isEmpty())
        ->assertSet('data.kategori_pengeluaran', fn (array $items) => collect($items)->pluck('nama_jenis')->filter()->isEmpty());
});

test('service default keuangan idempoten dan tidak mengubah nama default', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $bukuKas = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Operasional',
        'saldo' => 75000,
        'is_default' => true,
    ]);
    $dompet = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Tunai Toko',
        'saldo' => 75000,
        'is_default' => true,
    ]);

    $service = app(PastikanAkunKeuanganDefault::class);
    $service->jalankan($user);
    $service->jalankan($user);

    expect($user->buku_kas()->count())->toBe(1)
        ->and($user->dompet()->count())->toBe(1)
        ->and($bukuKas->fresh()->nama_buku)->toBe('Operasional')
        ->and($bukuKas->fresh()->is_default)->toBeTrue()
        ->and($dompet->fresh()->nama_dompet)->toBe('Tunai Toko')
        ->and($dompet->fresh()->is_default)->toBeTrue();
});

test('middleware memperbaiki akun yang hanya memiliki buku kas', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Warisan',
        'saldo' => 125000,
        'is_default' => false,
    ]);

    $this->actingAs($user)->followingRedirects()->get('/admin')->assertSuccessful();

    $bukuKas = $user->buku_kas()->firstOrFail();
    $dompet = $user->dompet()->firstOrFail();

    expect($bukuKas->is_default)->toBeTrue()
        ->and($dompet->nama_dompet)->toBe('Cash')
        ->and($dompet->saldo)->toBe(125000)
        ->and($dompet->is_default)->toBeTrue();
});

test('middleware memperbaiki akun yang hanya memiliki dompet', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Rekening Lama',
        'saldo' => 90000,
        'is_default' => false,
    ]);

    $this->actingAs($user)->followingRedirects()->get('/admin')->assertSuccessful();

    $bukuKas = $user->buku_kas()->firstOrFail();
    $dompet = $user->dompet()->firstOrFail();

    expect($bukuKas->nama_buku)->toBe('Kas Utama')
        ->and($bukuKas->saldo)->toBe(90000)
        ->and($bukuKas->is_default)->toBeTrue()
        ->and($dompet->is_default)->toBeTrue();
});
