<?php

use App\Models\Transaksi;
use Livewire\Livewire;

// ==================== AUTHORIZATION TESTS ====================

test('regular user tidak dapat mengakses user management', function () {
    $user = createRegularUserWithBukuKas();

    $this->get(route('filament.admin.resources.users.index'))
        ->assertForbidden();
})
    ->group('filament', 'authorization');

test('super user dapat mengakses user management', function () {
    $superUser = createSuperUser();

    $this->actingAs($superUser)
        ->get(route('filament.admin.resources.users.index'))
        ->assertSuccessful();
})
    ->group('filament', 'authorization');

test('regular user hanya dapat melihat data sendiri di transaksi', function () {
    $user1 = createRegularUserWithBukuKas();
    $user2 = createRegularUserWithBukuKas();

    $bukuKas2 = $user2->buku_kas()->first();

    Transaksi::factory()->create([
        'user_id' => $user2->id,
        'buku_kas_id' => $bukuKas2->id,
        'jenis' => 'Pemasukan',
        'nominal' => 200000,
    ]);

    $bukuKas1 = $user1->buku_kas()->first();

    Livewire::actingAs($user1)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->assertDontSee('200000');
})
    ->group('filament', 'authorization');
