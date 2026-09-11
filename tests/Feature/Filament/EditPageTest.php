<?php

use App\Filament\Resources\BukuKasResource\Pages\EditBukuKas;
use App\Filament\Resources\ShareBukuResource\Pages\ListShareBukus;
use App\Models\ShareBuku;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

// ==================== EDIT PAGES ====================

test('edit buku kas page dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(EditBukuKas::class, ['record' => $bukuKas->id])
        ->assertSuccessful();
})
    ->group('filament', 'edit');

test('edit buku kas page dapat update nama', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(EditBukuKas::class, ['record' => $bukuKas->id])
        ->fillForm(['nama_buku' => 'Kas Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($bukuKas->fresh()->nama_buku)->toBe('Kas Updated');
})
    ->group('filament', 'edit');

test('edit share buku modal dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    // Buat pengguna lain sebagai kolaborator.
    $otherUser = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
    ]);

    $shareBuku = ShareBuku::create([
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'viewer',
    ]);

    Livewire::actingAs($user)
        ->test(ListShareBukus::class)
        ->mountTableAction('edit', $shareBuku)
        ->assertActionMounted(TestAction::make('edit')->table($shareBuku))
        ->assertSuccessful();
})
    ->group('filament', 'edit');

test('edit share buku modal dapat update privilege', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $otherUser = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
    ]);

    $shareBuku = ShareBuku::create([
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'viewer',
    ]);

    Livewire::actingAs($user)
        ->test(ListShareBukus::class)
        ->assertTableActionExists('edit', fn ($action): bool => $action->getUrl() === null, $shareBuku)
        ->callTableAction('edit', $shareBuku, data: ['privilege' => 'editor'])
        ->assertHasNoTableActionErrors();

    expect($shareBuku->fresh()->privilege)->toBe('editor');
})
    ->group('filament', 'edit');
