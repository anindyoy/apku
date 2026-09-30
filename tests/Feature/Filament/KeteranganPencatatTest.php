<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\PencarianTransaksi;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\ShareBuku;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('keterangan pencatat hanya tampil pada kas dengan riwayat kolaborasi akun', function (string $page) {
    Notification::fake();
    $user = createRegularUserWithBukuKas();
    $kasPribadi = $user->buku_kas()->firstOrFail();
    $kasBersama = BukuKas::factory()->create(['user_id' => $user->id]);
    $share = ShareBuku::factory()->create(['buku_kas_id' => $kasBersama->id]);
    $share->delete();
    $records = collect([$kasPribadi, $kasBersama])->map(fn ($kas) => Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $kas->id,
        'jenis' => 'Pemasukan',
        'tanggal' => now(),
        'deskripsi' => 'Uji keterangan pencatat',
    ]));

    $component = Livewire::actingAs($user)->test($page);
    if ($page === PencarianTransaksi::class) {
        $component->searchTable('Uji keterangan pencatat');
    }
    $component->assertCanSeeTableRecords($records);
    $loaded = $component->instance()->getTableRecords()->keyBy('id');
    foreach ($records as $index => $record) {
        $expected = $index === 0 ? null : 'Dicatat oleh: '.$user->name;
        $component->assertTableColumnExists('tanggal', fn ($column): bool => $column->getDescriptionBelow() === $expected, $loaded[$record->id]);
    }
})->with([ListTransaksis::class, PencarianTransaksi::class, Dashboard::class]);

test('riwayat kolaborasi akun tetap tersimpan setelah kedaluwarsa dan pencabutan', function () {
    Notification::fake();
    $owner = User::factory()->create();
    foreach (['aktif', 'kedaluwarsa'] as $status) {
        $kas = BukuKas::factory()->create(['user_id' => $owner->id]);
        $share = ShareBuku::factory()->create([
            'buku_kas_id' => $kas->id,
            'berlaku_mulai' => now()->subDays(2),
            'berlaku_sampai' => $status === 'aktif' ? now()->addDay() : now()->subDay(),
        ]);
        expect($kas->refresh()->pernah_dikolaborasikan)->toBeTrue();
        $share->delete();
        expect($kas->refresh()->pernah_dikolaborasikan)->toBeTrue();
    }

    $kas = BukuKas::factory()->create(['user_id' => $owner->id]);
    ShareBuku::factory()->create(['buku_kas_id' => $kas->id, 'user_id' => null]);
    expect($kas->refresh()->pernah_dikolaborasikan)->toBeFalse();
    ShareBuku::factory()->create(['buku_kas_id' => $kas->id, 'user_id' => $owner->id]);
    expect($kas->refresh()->pernah_dikolaborasikan)->toBeFalse();
});
