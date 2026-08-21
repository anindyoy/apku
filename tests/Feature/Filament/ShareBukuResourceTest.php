<?php

use App\Models\User;
use App\Models\ShareBuku;
use Livewire\Livewire;

// ==================== SHARE BUKU RESOURCE ====================

test('share buku resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $otherUser = User::factory()->create();

    ShareBuku::factory()->create([
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'edit',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\ShareBukuResource\Pages\ListShareBukus::class)
        ->assertSuccessful();
})
    ->group('filament', 'share-buku');

test('share buku resource dapat membuat share baru', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $otherUser = User::factory()->create();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\ShareBukuResource\Pages\CreateShareBuku::class)
        ->assertSuccessful()
        ->set('data.buku_kas_id', $bukuKas->id)
        ->set('data.user_id', $otherUser->id)
        ->set('data.privilege', 'view')
        ->call('create')
        ->assertHasNoErrors()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('share_bukus', [
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'view',
    ]);
})
    ->group('filament', 'share-buku');
