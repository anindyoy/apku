<?php

use App\Filament\Resources\VoucherResource;
use App\Filament\Resources\VoucherResource\Pages\ListVouchers;
use App\Filament\Resources\VoucherResource\Pages\ManageVoucherCodes;
use App\Models\Langganan;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherCode;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

test('kelola kode voucher tersedia dari voucher tanpa resource terpisah', function () {
    $voucher = Voucher::factory()->create();
    Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(ListVouchers::class)
        ->assertTableActionExists('kelolaKode', fn ($action): bool => $action->getUrl() === VoucherResource::getUrl('codes', ['record' => $voucher]), $voucher);

    expect(Route::has('filament.admin.resources.voucher-codes.index'))->toBeFalse();
});

test('kelola kode voucher membatasi daftar dan menyimpan tambah edit lewat modal', function () {
    $voucher = Voucher::factory()->create();
    $otherCode = VoucherCode::factory()->create();
    $component = Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(ManageVoucherCodes::class, ['record' => $voucher->id])
        ->assertCanNotSeeTableRecords([$otherCode])
        ->mountAction(TestAction::make('create')->table())
        ->assertActionMounted(TestAction::make('create')->table())
        ->assertFormFieldDoesNotExist('voucher_id')
        ->fillForm(['code' => ''])
        ->callMountedAction()
        ->assertHasActionErrors(['code' => 'required'])
        ->fillForm(['code' => $otherCode->code])
        ->callMountedAction()
        ->assertHasActionErrors(['code' => 'unique'])
        ->fillForm(['code' => ' kodebaru '])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNoRedirect();

    $code = $voucher->codes()->sole();
    expect($code->code)->toBe('KODEBARU');
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

test('kelola kode voucher melindungi kode yang sudah dipakai', function () {
    $code = VoucherCode::factory()->create();
    Langganan::factory()->create(['voucher_code_id' => $code->id]);

    Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(ManageVoucherCodes::class, ['record' => $code->voucher_id])
        ->assertTableActionHidden('delete', $code);
});
