<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use App\Filament\Resources\PiutangResource\Pages\PiutangDetail;

// ==================== PIUTANG DETAIL - TABLE ROW ACTIONS ====================
// Targets uncovered lines in PiutangDetail.php table() method
// Note: Page header actions (ubah/hapus via ActionsAction) cannot be tested
// via Livewire test helper in Filament v4 (returns Response, not Testable).

function createPiutangDetailTestData(User $user, string $kepada, int $nominal = 100000): UtangPiutang
{
    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => $kepada,
        'tipe' => 'piutang',
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $piutang->id,
        'nominal' => $nominal,
        'tipe' => 'tambah',
        'created_at' => now(),
    ]);

    return $piutang;
}

// --- Table Row Action: ubah detail ---

test('piutang detail - tabel action ubah detail berhasil', function () {
    $user = User::notSuper()->inRandomOrder()->first();
    $piutang = createPiutangDetailTestData($user, 'Budi Setiawan', 100000);

    $detail = UtangPiutangDetail::where('utang_piutang_id', $piutang->id)->first();

    Livewire::actingAs($user)
        ->test(PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->callTableAction('ubah', $detail, data: [
            'nominal' => 150000,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'deskripsi' => 'Detail updated',
        ])
        ->assertHasNoTableActionErrors();

    $detail->refresh();
    $this->assertEquals(150000, $detail->nominal);
})->group('piutang-detail-action');

// --- Table Row Action: delete detail (DeleteAction::make() defaults to name 'delete') ---

test('piutang detail - tabel action delete detail berhasil', function () {
    $user = User::notSuper()->inRandomOrder()->first();
    $piutang = createPiutangDetailTestData($user, 'Detail Dihapus', 100000);

    $detail = UtangPiutangDetail::where('utang_piutang_id', $piutang->id)->first();

    Livewire::actingAs($user)
        ->test(PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->callTableAction('delete', $detail);

    $this->assertDatabaseMissing('utang_piutang_detail', ['id' => $detail->id]);
})->group('piutang-detail-action');

// --- Title and subheading ---

test('piutang detail - judul halaman benar', function () {
    $user = User::notSuper()->inRandomOrder()->first();
    $piutang = createPiutangDetailTestData($user, 'Judul Test', 100000);

    Livewire::actingAs($user)
        ->test(PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertSeeText('Piutang kepada Judul Test');
})->group('piutang-detail-action');

test('piutang detail - subheading jatuh tempo muncul jika ada tempo', function () {
    $user = User::notSuper()->inRandomOrder()->first();

    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => 'Dengan Tempo',
        'tipe' => 'piutang',
        'tempo' => now()->addDays(30),
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $piutang->id,
        'nominal' => 100000,
        'tipe' => 'tambah',
        'created_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertSeeText('Jatuh tempo');
})->group('piutang-detail-action');

test('piutang detail - subheading tidak muncul jika tanpa tempo', function () {
    $user = User::notSuper()->inRandomOrder()->first();
    $piutang = createPiutangDetailTestData($user, 'Tanpa Tempo', 100000);

    // Ensure no tempo
    $piutang->update(['tempo' => null]);

    Livewire::actingAs($user)
        ->test(PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertDontSee('Jatuh tempo');
})->group('piutang-detail-action');
