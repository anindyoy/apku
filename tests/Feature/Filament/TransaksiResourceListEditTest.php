<?php

use App\Filament\Resources\TransaksiResource\Pages\EditTransaksi;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\JenisTransaksi;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Services\TransaksiService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Livewire\Livewire;

// Pengujian halaman daftar dan edit transaksi.

test('transaksi resource - list page menampilkan kolom yang benar', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'deskripsi' => 'Test deskripsi transaksi',
        'jenis_transaksi_id' => $jenis->id,
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertSeeText('Rp')
        ->assertSeeText('Test deskripsi transaksi');
})
    ->group('filament', 'transaksi');

test('transaksi resource - tombol ubah pada daftar membuka modal edit', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();
    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
        'tipe' => 'Pemasukan',
    ]);
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'deskripsi' => 'Transaksi yang akan diubah',
        'jenis_transaksi_id' => $jenis->id,
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->mountTableAction('edit', $transaksi)
        ->assertSet('mountedActions.0.name', 'edit')
        ->assertSchemaStateSet([
            'nominal' => 100000,
            'deskripsi' => 'Transaksi yang akan diubah',
        ])
        ->assertFormFieldExists('nominal', fn (TextInput $field): bool => $field->getPrefixLabel() === 'Rp');
})
    ->group('filament', 'transaksi', 'ubah-transaksi');

test('form ubah transfer menampilkan kas dan dompet yang dapat dipilih serta menonaktifkan opsi premium', function () {
    $user = createRegularUserWithBukuKas();
    $user->update(['masa_aktif' => today()->subDay()]);
    $kasAsal = $user->buku_kas()->firstOrFail();
    $kasTujuan = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Kedua', 'saldo' => 0]);
    $kasTerbatas = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Ketiga', 'saldo' => 0]);
    $dompetAsal = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Cash', 'saldo' => 100000, 'is_default' => true]);
    $dompetKedua = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Bank', 'saldo' => 0]);
    $dompetTerbatas = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Dompet Ketiga', 'saldo' => 0]);
    $pasangan = app(TransaksiService::class)->transferBukuKas($user, $kasAsal, $kasTujuan, $dompetAsal, $dompetAsal, 10000);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->mountTableAction('edit', $pasangan['masuk'])
        ->assertSchemaStateSet([
            'buku_kas_id' => $kasAsal->id,
            'buku_kas_id_tujuan' => $kasTujuan->id,
            'dompet_id' => $dompetAsal->id,
        ])
        ->assertFormFieldExists('buku_kas_id', fn (Select $field): bool => ! $field->isDisabled()
            && ! $field->isOptionDisabled($kasAsal->id, $kasAsal->nama_buku)
            && $field->isOptionDisabled($kasTerbatas->id, $kasTerbatas->nama_buku))
        ->assertFormFieldExists('buku_kas_id_tujuan', fn (Select $field): bool => ! $field->isOptionDisabled($kasTujuan->id, $kasTujuan->nama_buku))
        ->assertFormFieldExists('dompet_id', fn (Select $field): bool => ! $field->isDisabled()
            && ! $field->isOptionDisabled($dompetKedua->id, $dompetKedua->nama_dompet)
            && $field->isOptionDisabled($dompetTerbatas->id, $dompetTerbatas->nama_dompet))
        ->unmountTableAction()
        ->callTableAction('edit', $pasangan['masuk'], data: [
            'buku_kas_id' => $kasTujuan->id,
            'buku_kas_id_tujuan' => $kasAsal->id,
            'dompet_id' => $dompetKedua->id,
            'tanggal' => $pasangan['masuk']->tanggal,
            'nominal' => 15000,
        ])
        ->assertHasNoTableActionErrors();

    expect($pasangan['keluar']->fresh()->buku_kas_id)->toBe($kasTujuan->id)
        ->and($pasangan['masuk']->fresh()->buku_kas_id)->toBe($kasAsal->id)
        ->and($pasangan['keluar']->fresh()->dompet_id)->toBe($dompetKedua->id)
        ->and($pasangan['masuk']->fresh()->dompet_id)->toBe($dompetKedua->id);
})
    ->group('filament', 'transaksi', 'ubah-transaksi');

test('transaksi resource - tombol tambah membuka modal transaksi', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertActionVisible('Tambah transaksi')
        ->mountAction('Tambah transaksi')
        ->assertSet('mountedActions.0.name', 'Tambah transaksi')
        ->assertActionDataSet(['jenis_form' => 'pemasukan'])
        ->assertFormFieldExists('jenis_form', function (ToggleButtons $field): bool {
            expect($field->isGrouped())->toBeFalse()
                ->and($field->getColumns('default'))->toBe(2)
                ->and($field->getColumns('sm'))->toBe(2)
                ->and($field->getOptions())->toHaveCount(4);

            return true;
        });
})
    ->group('filament', 'transaksi', 'tambah-transaksi');

test('warna tombol submit mengikuti jenis transaksi yang dipilih', function () {
    $user = createRegularUserWithBukuKas();
    $komponen = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->mountAction('Tambah transaksi');

    foreach ([
        'pemasukan' => 'success',
        'pengeluaran' => 'danger',
        'transfer_kas' => 'info',
        'transfer_dompet' => 'warning',
    ] as $jenisForm => $warna) {
        $komponen->set('mountedActions.0.data.jenis_form', $jenisForm);

        expect($komponen->instance()->getMountedAction()?->getModalSubmitAction()?->getColor())
            ->toBe($warna);
    }
})
    ->group('filament', 'transaksi', 'tambah-transaksi', 'warna-submit');

test('opsi transfer kas aktif tanpa filter ketika tersedia dua kas milik sendiri', function () {
    $user = createRegularUserWithBukuKas();
    $user->buku_kas()->create([
        'nama_buku' => 'Kas kedua',
        'saldo' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->mountAction('Tambah transaksi')
        ->assertFormFieldExists('jenis_form', checkFieldUsing: fn (ToggleButtons $field): bool => ! $field->isOptionDisabled(
            'transfer_kas',
            'Transfer kas',
        ));
})
    ->group('filament', 'transaksi', 'tambah-transaksi', 'transfer-kas');

test('field modal transfer hanya menampilkan sumber dan tujuan yang relevan', function () {
    $user = createRegularUserWithBukuKas();
    $user->buku_kas()->create(['nama_buku' => 'Kas kedua', 'saldo' => 0]);

    $komponen = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->mountAction('Tambah transaksi')
        ->set('mountedActions.0.data.jenis_form', 'transfer_kas')
        ->assertFormFieldVisible('buku_kas_id')
        ->assertFormFieldVisible('buku_kas_id_tujuan')
        ->assertFormFieldHidden('dompet_id')
        ->assertFormFieldHidden('dompet_id_tujuan');

    $komponen
        ->set('mountedActions.0.data.jenis_form', 'transfer_dompet')
        ->assertFormFieldHidden('buku_kas_id')
        ->assertFormFieldHidden('buku_kas_id_tujuan')
        ->assertFormFieldVisible('dompet_id')
        ->assertFormFieldVisible('dompet_id_tujuan');
})
    ->group('filament', 'transaksi', 'tambah-transaksi', 'field-transfer');

test('input dan tombol submit nonaktif selama perubahan jenis transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $komponen = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->mountAction('Tambah transaksi');

    $submit = $komponen->instance()->getMountedAction()?->getModalSubmitAction();
    $schema = $komponen->instance()->getSchema($komponen->instance()->getMountedActionSchemaName());
    $grid = collect($schema?->getComponents())
        ->first(fn ($component): bool => $component instanceof Grid);

    expect(ListTransaksis::targetLoadingPerubahanJenisForm())
        ->toBe('mountedActions.0.data.jenis_form')
        ->and($submit?->getExtraAttributes())
        ->toMatchArray([
            'wire:loading.attr' => 'disabled',
            'wire:target' => 'mountedActions.0.data.jenis_form',
        ])
        ->and($grid)->toBeInstanceOf(Grid::class)
        ->and($grid?->getExtraAttributes())->toMatchArray([
            'x-data' => '{ changingTransactionType: false }',
            'x-bind:inert' => 'changingTransactionType',
            'x-bind:class' => "{ 'transaction-form-changing': changingTransactionType }",
            'wire:loading.attr' => 'inert',
            'wire:loading.class' => 'transaction-form-changing pointer-events-none',
            'wire:target' => 'mountedActions.0.data.jenis_form',
        ])
        ->and($grid?->getExtraAttributes()['x-on:change.capture'] ?? '')
        ->toContain('changingTransactionType = true')
        ->toContain('$wire.$hook(\'commit\'')
        ->toContain('changingTransactionType = false');

    $css = file_get_contents(resource_path('css/filament-toolbar.css'));

    expect($css)
        ->toContain('.transaction-form-changing .fi-input-wrp')
        ->toContain('background-color: #f3f4f6 !important;')
        ->toContain('.transaction-form-changing .fi-fo-toggle-buttons .fi-btn')
        ->toContain('cursor: wait !important;');
})
    ->group('filament', 'transaksi', 'tambah-transaksi', 'loading-jenis');

test('transaksi resource - edit page dapat update nominal', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'deskripsi' => 'Deskripsi awal',
        'jenis_transaksi_id' => $jenis->id,
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
        ->assertSuccessful()
        ->set('data.nominal', 200000)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals(200000, $transaksi->fresh()->nominal);
})
    ->group('filament', 'transaksi');

test('transaksi resource - edit page dapat update deskripsi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'deskripsi' => 'Deskripsi awal',
        'jenis_transaksi_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
        ->assertSuccessful()
        ->set('data.deskripsi', 'Deskripsi baru yang diubah')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('Deskripsi baru yang diubah', $transaksi->fresh()->deskripsi);
})
    ->group('filament', 'transaksi');

test('transaksi resource - list page dengan pengeluaran', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pengeluaran',
    ]);

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pengeluaran',
        'nominal' => 50000,
        'deskripsi' => 'Test pengeluaran',
        'jenis_transaksi_id' => $jenis->id,
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertSeeText('Rp')
        ->assertSeeText('Test pengeluaran');
})
    ->group('filament', 'transaksi');

test('transaksi resource - edit page validasi nominal required', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'jenis_transaksi_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
        ->assertSuccessful()
        ->set('data.nominal', null)
        ->call('save')
        ->assertHasErrors(['data.nominal']);
})
    ->group('filament', 'transaksi');

test('transaksi resource - list page kas overview widget ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'widgets');
