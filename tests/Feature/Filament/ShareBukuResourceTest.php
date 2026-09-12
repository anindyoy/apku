<?php

use App\Filament\Resources\ShareBukuResource;
use App\Filament\Resources\ShareBukuResource\Pages\ListShareBukus;
use App\Models\ShareBuku;
use App\Models\User;
use App\Notifications\BukuDibagikan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
            'user_id' => $otherUser->email,
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
            'user_id' => $share->user->email,
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

test('share buku email menggunakan input teks dan status sesuai pendaftaran', function () {
    $owner = createRegularUserWithBukuKas();
    $member = User::factory()->create();
    $unrelated = User::factory()->create();

    $component = Livewire::actingAs($owner)->test(ListShareBukus::class)->mountAction('create');
    $component->assertFormFieldExists('user_id', function ($field): bool {
        return $field instanceof TextInput && $field->getHint() === null;
    });
    expect($component->html())->not->toContain($member->email, $unrelated->email);

    $component->fillForm(['user_id' => $member->email])
        ->assertFormFieldExists('user_id', fn ($field): bool => $field->getHint() === 'Email sudah terdaftar di aplikasi.' && $field->getHintColor() === 'success');
    $component->fillForm(['user_id' => 'belum-terdaftar@example.com'])
        ->assertFormFieldExists('user_id', fn ($field): bool => $field->getHint() === 'Email belum terdaftar di aplikasi.' && $field->getHintColor() === 'danger');
});

test('share buku email menolak email yang tidak dapat menjadi kolaborator', function (string $type) {
    $owner = createRegularUserWithBukuKas();
    $email = match ($type) {
        'belum terdaftar' => 'belum-terdaftar@example.com',
        'format salah' => 'bukan-email',
        'sendiri' => $owner->email,
        'admin' => User::factory()->create(['role' => 'admin'])->email,
        'belum verifikasi' => User::factory()->create(['email_verified_at' => null])->email,
    };
    $kas = $owner->buku_kas()->first();

    Livewire::actingAs($owner)->test(ListShareBukus::class)
        ->callAction('create', data: ['buku_kas_id' => $kas->id, 'user_id' => $email, 'privilege' => 'viewer'])
        ->assertHasActionErrors(['user_id']);

    $this->assertDatabaseMissing('share_buku', ['buku_kas_id' => $kas->id]);
})->with(['belum terdaftar', 'format salah', 'sendiri', 'admin', 'belum verifikasi']);

test('share buku email pada modal edit tetap milik kolaborator semula', function () {
    $owner = createRegularUserWithBukuKas();
    $member = User::factory()->create();
    $other = User::factory()->create();
    $share = ShareBuku::factory()->create(['buku_kas_id' => $owner->buku_kas()->first()->id, 'user_id' => $member->id]);

    Livewire::actingAs($owner)->test(ListShareBukus::class)
        ->mountTableAction('edit', $share)
        ->assertFormFieldExists('user_id', fn ($field): bool => $field->isDisabled() && $field->getState() === $member->email)
        ->fillForm(['user_id' => $other->email, 'privilege' => 'editor'])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($share->fresh()->user_id)->toBe($member->id);
});

test('share buku tanggal akses menggunakan input tanggal pada modal tambah dan edit', function () {
    Notification::fake();
    $owner = createRegularUserWithBukuKas();
    $member = User::factory()->create();
    $kas = $owner->buku_kas()->first();
    $mulai = today()->addDay()->toDateString();
    $akhir = today()->addDays(3)->toDateString();

    $component = Livewire::actingAs($owner)->test(ListShareBukus::class)->mountAction('create');
    foreach (['berlaku_mulai', 'berlaku_sampai'] as $name) {
        $component->assertFormFieldExists($name, fn ($field): bool => $field instanceof DatePicker && ! $field->hasTime());
    }
    $component->fillForm([
        'buku_kas_id' => $kas->id,
        'user_id' => $member->email,
        'privilege' => 'viewer',
        'berlaku_mulai' => $mulai,
        'berlaku_sampai' => $mulai,
    ])->callMountedAction()->assertHasActionErrors(['berlaku_sampai']);
    $component->fillForm(['berlaku_sampai' => $akhir])->callMountedAction()->assertHasNoActionErrors();

    $share = ShareBuku::where('buku_kas_id', $kas->id)->where('user_id', $member->id)->sole();
    expect($share->berlaku_mulai->format('Y-m-d H:i:s'))->toBe($mulai.' 00:00:00')
        ->and($share->berlaku_sampai->format('Y-m-d H:i:s'))->toBe($akhir.' 00:00:00');

    $component->mountTableAction('edit', $share)
        ->assertFormFieldExists('berlaku_mulai', fn ($field): bool => $field->getState() === $mulai && ! $field->hasTime())
        ->assertFormFieldExists('berlaku_sampai', fn ($field): bool => $field->getState() === $akhir && ! $field->hasTime())
        ->fillForm(['berlaku_sampai' => null])
        ->callMountedAction()->assertHasNoActionErrors();
    expect($share->fresh()->berlaku_sampai)->toBeNull();
});
