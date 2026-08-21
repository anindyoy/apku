<?php

use App\Models\User;
use Livewire\Livewire;

// ==================== USER RESOURCE ====================

test('user resource dapat menampilkan halaman list (super user only)', function () {
    // Hapus semua user factory agar tidak ada user lain yang mengganggu
    User::query()->where('email', '!=', 'super@test.com')->forceDelete();

    $superUser = createSuperUser();

    User::factory(5)->create();

    Livewire::actingAs($superUser)
        ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
        ->assertSuccessful()
        ->assertSee($superUser->name)
        ->assertSee($superUser->email);
})
    ->group('filament', 'user');

test('user resource dapat mengedit user (super user only)', function () {
    $superUser = createSuperUser();
    $user = User::factory()->create();

    Livewire::actingAs($superUser)
        ->test(\App\Filament\Resources\UserResource\Pages\EditUser::class, ['record' => $user->id])
        ->assertSuccessful()
        ->set('data.name', 'Updated Name')
        ->set('data.password', 'password')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('Updated Name', $user->fresh()->name);
})
    ->group('filament', 'user');
