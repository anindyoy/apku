<?php

use App\Filament\Resources\ShareBukuResource;
use App\Filament\Resources\ShareBukuResource\Pages\ListShareBukus;
use App\Models\ShareBuku;
use App\Models\User;
use App\Notifications\BukuDibagikan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('share buku resource menggunakan label kolaborator kas', function () {
    expect(ShareBukuResource::getNavigationLabel())->toBe('Kolaborator Kas')
        ->and(ShareBukuResource::getModelLabel())->toBe('Kolaborator Kas')
        ->and(ShareBukuResource::getPluralModelLabel())->toBe('Kolaborator Kas');
})->group('filament', 'share-buku', 'label-kolaborator-kas');

// ==================== SHARE BUKU RESOURCE ====================

test('share buku resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $otherUser = User::factory()->create();

    ShareBuku::factory()->create([
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'editor',
    ]);

    Livewire::actingAs($user)
        ->test(ListShareBukus::class)
        ->assertSuccessful();
})
    ->group('filament', 'share-buku');

test('share buku resource dapat membuat share baru melalui modal', function () {
    Notification::fake();
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $otherUser = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ListShareBukus::class)
        ->assertSuccessful()
        ->assertActionExists('create', fn ($action): bool => $action->getUrl() === null)
        ->callAction('create', data: [
            'buku_kas_id' => $bukuKas->id,
            'user_id' => $otherUser->id,
            'privilege' => 'viewer',
        ])
        ->assertHasNoErrors();

    $this->assertDatabaseHas('share_buku', [
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'viewer',
        'invited_by_user_id' => $user->id,
    ]);
    Notification::assertSentTo($otherUser, BukuDibagikan::class);
})
    ->group('filament', 'share-buku');

test('share buku resource menolak duplikat melalui modal', function () {
    $user = createRegularUserWithBukuKas();
    $share = ShareBuku::factory()->create(['buku_kas_id' => $user->buku_kas()->first()->id]);

    Livewire::actingAs($user)
        ->test(ListShareBukus::class)
        ->callAction('create', data: [
            'buku_kas_id' => $share->buku_kas_id,
            'user_id' => $share->user_id,
            'privilege' => 'viewer',
        ])
        ->assertHasActionErrors(['user_id']);

    expect(ShareBuku::where('buku_kas_id', $share->buku_kas_id)->where('user_id', $share->user_id)->count())->toBe(1);
});

test('share buku resource dapat mencabut akses melalui modal', function () {
    $user = createRegularUserWithBukuKas();
    $share = ShareBuku::factory()->create(['buku_kas_id' => $user->buku_kas()->first()->id]);

    Livewire::actingAs($user)
        ->test(ListShareBukus::class)
        ->callTableAction('delete', $share)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('share_buku', ['id' => $share->id]);
});
