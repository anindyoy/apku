<?php

use App\Models\BukuKas;
use App\Models\ShareBuku;
use App\Models\UtangPiutang;
use Livewire\Livewire;

// ==================== EDIT PAGES ====================

test('edit buku kas page dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\EditBukuKas::class, ['record' => $bukuKas->id])
        ->assertSuccessful();
})
    ->group('filament', 'edit');

test('edit buku kas page dapat update nama', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\EditBukuKas::class, ['record' => $bukuKas->id])
        ->fillForm(['nama_buku' => 'Kas Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($bukuKas->fresh()->nama_buku)->toBe('Kas Updated');
})
    ->group('filament', 'edit');

test('edit share buku page dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    // Create another user to share with
    $otherUser = \App\Models\User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
    ]);

    $shareBuku = ShareBuku::create([
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'viewer',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\ShareBukuResource\Pages\EditShareBuku::class, ['record' => $shareBuku->id])
        ->assertSuccessful();
})
    ->group('filament', 'edit');

test('edit share buku page dapat update privilege', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $otherUser = \App\Models\User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
    ]);

    $shareBuku = ShareBuku::create([
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'viewer',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\ShareBukuResource\Pages\EditShareBuku::class, ['record' => $shareBuku->id])
        ->fillForm(['privilege' => 'editor'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($shareBuku->fresh()->privilege)->toBe('editor');
})
    ->group('filament', 'edit');
