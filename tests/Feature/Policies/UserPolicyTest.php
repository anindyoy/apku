<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

// ==================== USER POLICY ====================

test('super user dapat viewAny user', function () {
    $superUser = createSuperUser();

    $this->actingAs($superUser);
    $this->assertTrue(Gate::forUser($superUser)->allows('viewAny', User::class));
})
    ->group('policies', 'authorization');

test('regular user tidak dapat viewAny user', function () {
    $user = createRegularUserWithBukuKas();

    $this->actingAs($user);
    $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
})
    ->group('policies', 'authorization');

test('super user dapat view user', function () {
    $superUser = createSuperUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($superUser);
    $this->assertTrue(Gate::forUser($superUser)->allows('view', $user));
})
    ->group('policies', 'authorization');

test('regular user tidak dapat view user lain', function () {
    $user = createRegularUserWithBukuKas();
    $otherUser = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);
    $this->assertFalse(Gate::forUser($user)->allows('view', $otherUser));
})
    ->group('policies', 'authorization');

test('super user dapat create user', function () {
    $superUser = createSuperUser();

    $this->actingAs($superUser);
    $this->assertTrue(Gate::forUser($superUser)->allows('create', User::class));
})
    ->group('policies', 'authorization');

test('regular user tidak dapat create user', function () {
    $user = createRegularUserWithBukuKas();

    $this->actingAs($user);
    $this->assertFalse(Gate::forUser($user)->allows('create', User::class));
})
    ->group('policies', 'authorization');

test('super user dapat update user', function () {
    $superUser = createSuperUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($superUser);
    $this->assertTrue(Gate::forUser($superUser)->allows('update', $user));
})
    ->group('policies', 'authorization');

test('regular user tidak dapat update user lain', function () {
    $user = createRegularUserWithBukuKas();
    $otherUser = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);
    $this->assertFalse(Gate::forUser($user)->allows('update', $otherUser));
})
    ->group('policies', 'authorization');

test('super user dapat delete user', function () {
    $superUser = createSuperUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($superUser);
    $this->assertTrue(Gate::forUser($superUser)->allows('delete', $user));
})
    ->group('policies', 'authorization');

test('regular user tidak dapat delete user', function () {
    $user = createRegularUserWithBukuKas();
    $otherUser = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);
    $this->assertFalse(Gate::forUser($user)->allows('delete', $otherUser));
})
    ->group('policies', 'authorization');

test('super user dapat restore user', function () {
    $superUser = createSuperUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($superUser);
    $this->assertTrue(Gate::forUser($superUser)->allows('restore', $user));
})
    ->group('policies', 'authorization');

test('super user dapat forceDelete user', function () {
    $superUser = createSuperUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($superUser);
    $this->assertTrue(Gate::forUser($superUser)->allows('forceDelete', $user));
})
    ->group('policies', 'authorization');
