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
