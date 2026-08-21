<?php

use Livewire\Livewire;

// ==================== BUKU KAS RESOURCE ====================

test('buku kas resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\ListBukuKas::class)
        ->assertSuccessful()
        ->assertSee('Kas Test');
})
    ->group('filament', 'buku-kas');

test('buku kas resource dapat membuat record baru', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\CreateBukuKas::class)
        ->assertSuccessful()
        ->set('data.nama_buku', 'Kas Baru')
        ->set('data.saldo', 50000)
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('buku_kas', [
        'user_id' => $user->id,
        'nama_buku' => 'Kas Baru',
        'saldo' => 50000,
    ]);
})
    ->group('filament', 'buku-kas');

test('buku kas resource dapat mengedit record', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\EditBukuKas::class, ['record' => $bukuKas->id])
        ->assertSuccessful()
        ->set('data.nama_buku', 'Kas Updated')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('Kas Updated', $bukuKas->fresh()->nama_buku);
})
    ->group('filament', 'buku-kas');

test('buku kas resource validasi form required', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\CreateBukuKas::class)
        ->assertSuccessful()
        ->set('data.nama_buku', '')
        ->set('data.saldo', '')
        ->call('create')
        ->assertHasErrors(['data.nama_buku' => 'required'])
        ->assertHasErrors(['data.saldo' => 'required']);
})
    ->group('filament', 'buku-kas');
