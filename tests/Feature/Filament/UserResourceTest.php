<?php

use App\Models\User;
use Livewire\Livewire;

// ==================== USER RESOURCE ====================

test('user resource dapat menampilkan halaman list (super user only)', function () {
    $superUser = createSuperUser();

    User::factory(5)->create();

    Livewire::actingAs($superUser)
        ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
        ->assertSuccessful()
        ->assertSee('Super Admin');
})
    ->group('filament', 'user');

test('user resource dapat mengedit user (super user only)', function () {
    $superUser = createSuperUser();
    $user = User::factory()->create();

    Livewire::actingAs($superUser)
        ->test(\App\Filament\Resources\UserResource\Pages\EditUser::class, ['record' => $user])
        ->assertSuccessful()
        ->set('data.name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('Updated Name', $user->fresh()->name);
})
    ->group('filament', 'user');
