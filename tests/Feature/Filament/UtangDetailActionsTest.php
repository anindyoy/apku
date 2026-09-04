<?php

use App\Filament\Resources\UtangResource\Pages\UtangDetail;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use Livewire\Livewire;

// ==================== UTANG DETAIL - TABLE ROW ACTIONS ====================
// Targets uncovered lines in UtangDetail.php table() method
// Note: Page header actions (ubah/hapus via ActionsAction) cannot be tested
// via Livewire test helper in Filament v4 (returns Response, not Testable).

function createUtangDetailTestData(User $user, string $kepada, int $nominal = 100000): UtangPiutang
{
    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => $kepada,
        'tipe' => 'utang',
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $utang->id,
        'nominal' => $nominal,
        'tipe' => 'tambah',
        'created_at' => now(),
    ]);

    return $utang;
}

// --- Table Row Action: ubah detail ---

test('utang detail - tabel action ubah detail berhasil', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $utang = createUtangDetailTestData($user, 'Budi Setiawan', 100000);

    $detail = UtangPiutangDetail::where('utang_piutang_id', $utang->id)->first();

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->callTableAction('ubah', $detail, data: [
            'nominal' => 150000,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'deskripsi' => 'Detail utang updated',
        ])
        ->assertHasNoTableActionErrors();

    $detail->refresh();
    $this->assertEquals(150000, $detail->nominal);
})->group('utang-detail-action');

// --- Table Row Action: delete detail (DeleteAction::make() defaults to name 'delete') ---

test('utang detail - tabel action delete detail berhasil', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $utang = createUtangDetailTestData($user, 'Detail Dihapus', 100000);

    $detail = UtangPiutangDetail::where('utang_piutang_id', $utang->id)->first();

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->callTableAction('delete', $detail);

    $this->assertDatabaseMissing('utang_piutang_detail', ['id' => $detail->id]);
})->group('utang-detail-action');

// --- Title and subheading ---

test('utang detail - judul halaman benar', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $utang = createUtangDetailTestData($user, 'Judul Utang Test', 100000);

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSeeText('Utang kepada Judul Utang Test');
})->group('utang-detail-action');

test('utang detail - subheading jatuh tempo muncul jika ada tempo', function () {
    $user = User::notAdmin()->inRandomOrder()->first();

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => 'Utang Dengan Tempo',
        'tipe' => 'utang',
        'tempo' => now()->addDays(15),
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $utang->id,
        'nominal' => 100000,
        'tipe' => 'tambah',
        'created_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSeeText('Jatuh tempo');
})->group('utang-detail-action');

test('utang detail - subheading tidak muncul jika tanpa tempo', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $utang = createUtangDetailTestData($user, 'Tanpa Tempo Utang', 100000);

    // Ensure no tempo
    $utang->update(['tempo' => null]);

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertDontSee('Jatuh tempo');
})->group('utang-detail-action');

// --- Test with null parent (edge case for getTitle and getSubheading) ---

test('utang detail - title fallback saat parent null', function () {
    $user = User::notAdmin()->inRandomOrder()->first();

    // Use a non-existent code
    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => 'non_existent_code_12345'])
        ->assertSuccessful()
        ->assertSeeText('Detail Utang/Piutang');
})->group('utang-detail-action');

// --- Table action with tambah and kurang ---

test('utang detail - tabel action tambah menambahkan detail', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $utang = createUtangDetailTestData($user, 'Tambah Detail', 100000);

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->callTableAction('tambah', data: [
            'nominal' => 50000,
            'deskripsi' => 'Tambah lagi',
            'created_at' => now()->format('Y-m-d H:i:s'),
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('utang_piutang_detail', [
        'utang_piutang_id' => $utang->id,
        'nominal' => 50000,
        'tipe' => 'tambah',
    ]);
})->group('utang-detail-action');

test('utang detail - tabel action kurang mencatat pembayaran', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $utang = createUtangDetailTestData($user, 'Bayar Utang', 200000);

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->callTableAction('kurang', data: [
            'nominal' => 75000,
            'deskripsi' => 'Bayar sebagian utang',
            'created_at' => now()->format('Y-m-d H:i:s'),
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('utang_piutang_detail', [
        'utang_piutang_id' => $utang->id,
        'nominal' => 75000,
        'tipe' => 'kurang',
    ]);
})->group('utang-detail-action');
