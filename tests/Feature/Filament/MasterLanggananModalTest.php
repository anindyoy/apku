<?php

use App\Filament\Resources\MetodePembayaranResource;
use App\Filament\Resources\PaketLanggananResource;
use App\Filament\Resources\VoucherResource;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

dataset('master langganan modal', [
    'voucher' => [VoucherResource::class, 'label', 'Voucher baru', 'Voucher ubah'],
    'metode pembayaran' => [MetodePembayaranResource::class, 'label', 'Bank baru', 'Bank ubah'],
    'paket langganan' => [PaketLanggananResource::class, 'label', 'Paket baru', 'Paket ubah'],
]);

test('master langganan dapat dibuat dan diedit melalui modal', function (string $resource, string $field, string $initial, string $updated) {
    $admin = User::factory()->create(['role' => 'admin']);
    $data = match ($resource) {
        VoucherResource::class => ['jumlah_diskon' => 25, 'masa_aktif' => null, 'dapat_dipakai_berulang' => true],
        MetodePembayaranResource::class => ['jenis' => 'bank', 'nama_penyedia' => 'Bank contoh', 'urutan' => 0, 'is_active' => true],
        PaketLanggananResource::class => ['harga' => 25000, 'durasi_hari' => 30, 'is_active' => true],
    };

    expect($resource::hasPage('create'))->toBeFalse()
        ->and($resource::hasPage('edit'))->toBeFalse();

    $component = Livewire::actingAs($admin)->test($resource::getPages()['index']->getPage())
        ->assertActionExists('create', fn ($action): bool => $action->getUrl() === null)
        ->mountAction('create')
        ->assertActionMounted('create')
        ->fillForm([...$data, $field => ''])
        ->callMountedAction()
        ->assertHasActionErrors([$field => 'required'])
        ->assertActionMounted('create')
        ->fillForm([...$data, $field => $initial])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNoRedirect();

    $model = $resource::getModel();
    $record = $model::query()->where($field, $initial)->sole();
    $component->assertCanSeeTableRecords([$record])
        ->assertTableActionExists('edit', fn ($action): bool => $action->getUrl() === null, $record)
        ->mountTableAction('edit', $record)
        ->assertActionMounted(TestAction::make('edit')->table($record))
        ->assertSchemaStateSet([$field => $initial])
        ->fillForm([$field => ''])
        ->callMountedAction()
        ->assertHasActionErrors([$field => 'required'])
        ->fillForm([$field => $updated])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNoRedirect();

    expect($record->fresh()->{$field})->toBe($updated);
})->with('master langganan modal');

test('master langganan modal tidak dapat diakses pengguna non admin', function (string $resource) {
    Livewire::actingAs(User::factory()->create(['role' => 'user']))
        ->test($resource::getPages()['index']->getPage())
        ->assertForbidden();
})->with([
    'voucher' => [VoucherResource::class],
    'metode pembayaran' => [MetodePembayaranResource::class],
    'paket langganan' => [PaketLanggananResource::class],
]);
