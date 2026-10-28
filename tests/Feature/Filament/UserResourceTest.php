<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;

// ==================== USER RESOURCE ====================

test('user resource dapat menampilkan halaman list (admin only)', function () {
    $adminUser = createAdminUser();

    $users = User::factory(5)->create();

    Livewire::actingAs($adminUser)
        ->test(ListUsers::class)
        ->assertSuccessful()
        ->set('tableRecordsPerPage', 25)
        ->assertCanSeeTableRecords($users->push($adminUser));
})
    ->group('filament', 'user');

test('user resource dapat mengedit user (admin only)', function () {
    $adminUser = createAdminUser();
    $user = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(EditUser::class, ['record' => $user->id])
        ->assertSuccessful()
        ->set('data.name', 'Updated Name')
        ->set('data.password', 'password')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('Updated Name', $user->fresh()->name);
})
    ->group('filament', 'user');

test('tipe akun di tabel dan form user resource ditampilkan dengan huruf awal kapital', function () {
    $adminUser = createAdminUser();
    $premiumUser = User::factory()->create(['type' => 'premium']);
    $regulerUser = User::factory()->create(['type' => 'reguler']);

    Livewire::actingAs($adminUser)
        ->test(ListUsers::class)
        ->assertSuccessful()
        ->assertTableColumnFormattedStateSet('type', 'Premium', $premiumUser)
        ->assertTableColumnFormattedStateSet('type', 'Reguler', $regulerUser);

    $component = Livewire::actingAs($adminUser)
        ->test(EditUser::class, ['record' => $premiumUser->id])
        ->assertSuccessful();

    expect($component->instance()->form->getComponent('type')->getState())->toBe('Premium');

    $component->set('data.type', 'Reguler')
        ->set('data.password', 'password')
        ->call('save')
        ->assertHasNoErrors();

    expect($premiumUser->fresh()->type)->toBe('reguler');
})
    ->group('filament', 'user');

test('admin dapat memverifikasi email pengguna yang belum terverifikasi dari tabel', function () {
    $adminUser = createAdminUser();
    $unverifiedUser = User::factory()->unverified()->create();

    expect($unverifiedUser->hasVerifiedEmail())->toBeFalse();

    Livewire::actingAs($adminUser)
        ->test(ListUsers::class)
        ->assertTableActionVisible('verifikasiEmail', $unverifiedUser)
        ->callTableAction('verifikasiEmail', $unverifiedUser)
        ->assertHasNoErrors();

    expect($unverifiedUser->fresh()->hasVerifiedEmail())->toBeTrue();
})
    ->group('filament', 'user');

test('aksi verifikasi email disembunyikan untuk pengguna yang sudah terverifikasi', function () {
    $adminUser = createAdminUser();
    $verifiedUser = User::factory()->create();

    expect($verifiedUser->hasVerifiedEmail())->toBeTrue();

    Livewire::actingAs($adminUser)
        ->test(ListUsers::class)
        ->assertTableActionHidden('verifikasiEmail', $verifiedUser);
})
    ->group('filament', 'user');

test('admin dapat impersonasi pengguna hanya pada environment local', function () {
    $adminUser = createAdminUser();
    $user = User::factory()->create();
    $adminTarget = User::factory()->create(['role' => 'admin']);

    app()['env'] = 'local';

    Livewire::actingAs($adminUser)
        ->test(ListUsers::class)
        ->assertTableActionVisible('impersonate', $user)
        ->assertTableActionHidden('impersonate', $adminTarget);

    app()['env'] = 'testing';

    Livewire::actingAs($adminUser)
        ->test(ListUsers::class)
        ->assertTableActionHidden('impersonate', $user);
})
    ->group('filament', 'user');

test('admin dapat memverifikasi email pengguna secara massal', function () {
    $adminUser = createAdminUser();
    $unverifiedUsers = User::factory(3)->unverified()->create();

    Livewire::actingAs($adminUser)
        ->test(ListUsers::class)
        ->callTableBulkAction('verifikasiEmail', $unverifiedUsers)
        ->assertHasNoErrors();

    foreach ($unverifiedUsers as $user) {
        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    }
})
    ->group('filament', 'user');

test('filter status verifikasi dapat memfilter pengguna terverifikasi dan belum terverifikasi', function () {
    $adminUser = createAdminUser();
    $unverifiedUser = User::factory()->unverified()->create();
    $verifiedUser = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(ListUsers::class)
        ->set('tableRecordsPerPage', 50)
        ->filterTable('email_verified_at', false)
        ->assertCanSeeTableRecords([$unverifiedUser])
        ->assertCanNotSeeTableRecords([$verifiedUser])
        ->filterTable('email_verified_at', true)
        ->assertCanSeeTableRecords([$verifiedUser])
        ->assertCanNotSeeTableRecords([$unverifiedUser]);
})
    ->group('filament', 'user');
