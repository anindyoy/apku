<?php

use App\Filament\Resources\VoucherResource\Pages\ListVouchers;
use App\Filament\Resources\VoucherResource\RelationManagers\CodesRelationManager;
use App\Models\Langganan;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherCode;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

test('kelola kode voucher tersedia dari voucher tanpa resource terpisah', function () {
    $voucher = Voucher::factory()->create();
    $component = Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(ListVouchers::class)
        ->assertTableActionExists('kelolaKode', fn ($action): bool => $action->getUrl() === null && ! $action->hasFormWrapper(), $voucher)
        ->mountTableAction('kelolaKode', $voucher)
        ->assertActionMounted(TestAction::make('kelolaKode')->table($voucher))
        ->assertNoRedirect();

    $schema = $component->instance()->getSchema($component->instance()->getMountedActionSchemaName());
    $embedded = $schema->getComponents()[0];
    expect($embedded->getComponent())->toBe(CodesRelationManager::class)
        ->and($embedded->getData()['ownerRecord']->id)->toBe($voucher->id);

    expect(Route::has('filament.admin.resources.voucher-codes.index'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.vouchers.codes'))->toBeFalse();
});

test('kelola kode voucher menambah kode lewat form di atas tabel dan mengedit lewat modal', function () {
    $voucher = Voucher::factory()->create();
    $otherCode = VoucherCode::factory()->create();
    $component = Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(CodesRelationManager::class, ['ownerRecord' => $voucher, 'pageClass' => ListVouchers::class])
        ->assertCanNotSeeTableRecords([$otherCode])
        ->assertActionDoesNotExist(TestAction::make('create')->table())
        ->set('kodeBaru', '   ')
        ->call('tambahKode')
        ->assertHasErrors(['kodeBaru' => 'required'])
        ->set('kodeBaru', ' '.strtolower($otherCode->code).' ')
        ->call('tambahKode')
        ->assertHasErrors(['kodeBaru' => 'unique'])
        ->set('kodeBaru', str_repeat('A', 256))
        ->call('tambahKode')
        ->assertHasErrors(['kodeBaru' => 'max'])
        ->set('kodeBaru', ' kodebaru ')
        ->call('tambahKode')
        ->assertHasNoErrors()
        ->assertSet('kodeBaru', '')
        ->assertSet('mountedActions', [])
        ->assertDispatched('kode-voucher-diperbarui')
        ->assertNoRedirect();

    $code = $voucher->codes()->sole();
    expect($code->code)->toBe('KODEBARU');
    expect($component->instance()->getTable()->getHeading())->toBe('');
    $document = new DOMDocument;
    @$document->loadHTML(mb_convert_encoding($component->html(), 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($document);
    expect($xpath->query('//form[@data-voucher-code-create]//input')->length)->toBe(1)
        ->and($xpath->query('//form[@data-voucher-code-create]/following::table')->length)->toBeGreaterThan(0);
    $component->assertCanSeeTableRecords([$code])
        ->mountTableAction('edit', $code)
        ->assertActionMounted(TestAction::make('edit')->table($code))
        ->assertSchemaStateSet(['code' => 'KODEBARU'])
        ->fillForm(['code' => 'kodeubah'])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNoRedirect();

    expect($code->fresh()->code)->toBe('KODEUBAH')
        ->and($code->fresh()->voucher_id)->toBe($voucher->id);
    $component->callTableAction('delete', $code)->assertHasNoTableActionErrors();
    expect($code->fresh())->toBeNull();
});

test('modal kode voucher memperbarui jumlah kode pada daftar voucher', function () {
    $voucher = Voucher::factory()->create();
    $component = Livewire::actingAs(User::factory()->create(['role' => 'admin']))->test(ListVouchers::class);
    expect($component->instance()->getTableRecords()->find($voucher->id)->codes_count)->toBe(0);

    VoucherCode::factory()->create(['voucher_id' => $voucher->id]);
    $component->dispatch('kode-voucher-diperbarui');
    expect($component->instance()->getTableRecords()->find($voucher->id)->codes_count)->toBe(1);
});

test('modal kode voucher menolak pengguna non admin', function () {
    Livewire::actingAs(User::factory()->create(['role' => 'user']))
        ->test(CodesRelationManager::class, [
            'ownerRecord' => Voucher::factory()->create(),
            'pageClass' => ListVouchers::class,
        ])->assertForbidden();
});

test('kelola kode voucher melindungi kode yang sudah dipakai', function () {
    $code = VoucherCode::factory()->create();
    Langganan::factory()->create(['voucher_code_id' => $code->id]);

    Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(CodesRelationManager::class, ['ownerRecord' => $code->voucher, 'pageClass' => ListVouchers::class])
        ->assertTableActionHidden('delete', $code);
});
