<?php

use App\Filament\Pages\Onboarding;
use App\Models\User;
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

    $this->assertDatabaseHas('transaksi', [
        'buku_kas_id' => $bukuKas->id,
        'nominal' => 150000,
        'jenis' => 'Pemasukan',
        'deskripsi' => 'Saldo awal',
    ]);
    $this->assertDatabaseHas('jenis_transaksi', ['user_id' => $user->id, 'tipe' => 'Pemasukan', 'nama_jenis' => 'Penjualan']);
    $this->assertDatabaseHas('jenis_transaksi', ['user_id' => $user->id, 'tipe' => 'Pengeluaran', 'nama_jenis' => 'Belanja Stok']);
});

test('placeholder tidak disimpan sebagai data awal', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->assertSet('data.nama_buku', null)
        ->assertSet('data.kategori_pemasukan', fn (array $items) => collect($items)->pluck('nama_jenis')->filter()->isEmpty())
        ->assertSet('data.kategori_pengeluaran', fn (array $items) => collect($items)->pluck('nama_jenis')->filter()->isEmpty());
});
