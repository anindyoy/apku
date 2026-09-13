<?php

use App\Filament\Resources\BukuKasResource\Pages\CreateBukuKas;
use App\Filament\Resources\BukuKasResource\Pages\EditBukuKas;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use Livewire\Livewire;

test('tabel kas memadatkan kolom dan tetap mencari deskripsi', function () {
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->first();
    $kas->update(['description' => 'Dana perjalanan keluarga']);
    \App\Models\TabunganEmas::factory()->count(2)->create(['buku_kas_id' => $kas->id]);

    $component = Livewire::actingAs($user)->test(ListBukuKas::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$kas]);
    $table = $component->instance()->getTable();
    $record = $component->instance()->getTableRecords()->firstWhere('id', $kas->id);

    expect(array_keys($table->getColumns()))->toBe(['nama_buku', 'akses', 'saldo', 'created_at', 'updated_at'])
        ->and($table->getColumn('nama_buku')->record($record)->getDescriptionBelow())->toBe('Dana perjalanan keluarga')
        ->and($table->getColumn('akses')->record($record)->getDescriptionBelow())->toBe('Milik saya')
        ->and($table->getColumn('saldo')->record($record)->getDescriptionBelow())->toBe('0 transaksi · 2 produk emas')
        ->and($table->getColumn('created_at')->isToggledHiddenByDefault())->toBeTrue()
        ->and($table->getColumn('updated_at')->isToggledHiddenByDefault())->toBeTrue();

    $component->searchTable('perjalanan')->assertCanSeeTableRecords([$kas])
        ->searchTable('tidak ditemukan')->assertCanNotSeeTableRecords([$kas])
        ->searchTable($kas->nama_buku)->assertCanSeeTableRecords([$kas]);
})->group('filament', 'buku-kas');

test('buku kas mengelompokkan aksi baris dalam menu aksi', function () {
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->first();

    $component = Livewire::actingAs($user)->test(ListBukuKas::class)
        ->assertSuccessful()
        ->assertTableActionVisible('edit', $kas)
        ->assertTableActionVisible('kolaborator', $kas)
        ->assertTableActionVisible('delete', $kas)
        ->assertTableActionHidden('hapusDanPindahkan', $kas)
        ->assertTableActionHidden('cekNilaiEmas', $kas)
        ->assertTableActionHidden('hargaEmasManual', $kas);

    $actions = $component->instance()->getTable()->getRecordActions();

    expect($actions)->toHaveCount(1)
        ->and($actions[0])->toBeInstanceOf(\Filament\Actions\ActionGroup::class)
        ->and($actions[0]->getLabel())->toBe('Aksi')
        ->and(array_map(fn ($action) => $action->getName(), $actions[0]->getActions()))->toBe([
            'edit', 'kolaborator', 'cekNilaiEmas', 'hargaEmasManual', 'delete', 'hapusDanPindahkan',
        ]);

    $component->callTableAction('edit', $kas, data: [
        'nama_buku' => 'Kas dari menu aksi',
        'saldo' => $kas->saldo,
    ])->assertHasNoTableActionErrors();

    expect($kas->fresh()->nama_buku)->toBe('Kas dari menu aksi');
})->group('filament', 'buku-kas');

// ==================== BUKU KAS RESOURCE ====================

test('buku kas resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertSuccessful()
        ->assertSeeText('Kas Test');
})
    ->group('filament', 'buku-kas');

test('buku kas resource menggunakan label kas', function () {
    expect(\App\Filament\Resources\BukuKasResource::getModelLabel())->toBe('Kas')
        ->and(\App\Filament\Resources\BukuKasResource::getPluralModelLabel())->toBe('Kas')
        ->and(\App\Filament\Resources\BukuKasResource::getNavigationLabel())->toBe('Kas');
})
    ->group('filament', 'buku-kas', 'label-kas');

test('buku kas resource dapat membuat record baru', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(CreateBukuKas::class)
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

test('pengguna tidak dapat membuat buku kas dengan nama yang sama', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(CreateBukuKas::class)
        ->set('data.nama_buku', 'Kas Test')
        ->set('data.saldo', 50000)
        ->call('create')
        ->assertHasErrors(['data.nama_buku' => 'unique']);

    expect($user->buku_kas()->where('nama_buku', 'Kas Test')->count())->toBe(1);
})
    ->group('filament', 'buku-kas');

test('pengguna berbeda dapat memakai nama buku kas yang sama', function () {
    $userPertama = createRegularUserWithBukuKas();
    $userKedua = createRegularUserWithBukuKas();

    expect($userPertama->buku_kas()->where('nama_buku', 'Kas Test')->count())->toBe(1)
        ->and($userKedua->buku_kas()->where('nama_buku', 'Kas Test')->count())->toBe(1);
})
    ->group('filament', 'buku-kas');

test('buku kas resource dapat mengedit record', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(EditBukuKas::class, ['record' => $bukuKas->id])
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
        ->test(CreateBukuKas::class)
        ->assertSuccessful()
        ->set('data.nama_buku', '')
        ->set('data.saldo', '')
        ->call('create')
        ->assertHasErrors(['data.nama_buku' => 'required'])
        ->assertHasErrors(['data.saldo' => 'required']);
})
    ->group('filament', 'buku-kas');
