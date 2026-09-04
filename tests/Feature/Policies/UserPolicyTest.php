<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

// ==================== USER POLICY ====================

test('admin dapat viewAny user', function () {
    $adminUser = createAdminUser();

    $this->actingAs($adminUser);
    $this->assertTrue(Gate::forUser($adminUser)->allows('viewAny', User::class));
})
    ->group('policies', 'authorization');

test('regular user tidak dapat viewAny user', function () {
    $user = createRegularUserWithBukuKas();

    $this->actingAs($user);
    $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
})
    ->group('policies', 'authorization');

test('admin dapat view user', function () {
    $adminUser = createAdminUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($adminUser);
    $this->assertTrue(Gate::forUser($adminUser)->allows('view', $user));
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

test('admin dapat create user', function () {
    $adminUser = createAdminUser();

    $this->actingAs($adminUser);
    $this->assertTrue(Gate::forUser($adminUser)->allows('create', User::class));
})
    ->group('policies', 'authorization');

test('regular user tidak dapat create user', function () {
    $user = createRegularUserWithBukuKas();

    $this->actingAs($user);
    $this->assertFalse(Gate::forUser($user)->allows('create', User::class));
})
    ->group('policies', 'authorization');

test('admin dapat update user', function () {
    $adminUser = createAdminUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($adminUser);
    $this->assertTrue(Gate::forUser($adminUser)->allows('update', $user));
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

test('admin dapat delete user', function () {
    $adminUser = createAdminUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($adminUser);
    $this->assertTrue(Gate::forUser($adminUser)->allows('delete', $user));
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

test('admin dapat restore user', function () {
    $adminUser = createAdminUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($adminUser);
    $this->assertTrue(Gate::forUser($adminUser)->allows('restore', $user));
})
    ->group('policies', 'authorization');

test('admin dapat forceDelete user', function () {
    $adminUser = createAdminUser();
    $user = createRegularUserWithBukuKas();

    $this->actingAs($adminUser);
    $this->assertTrue(Gate::forUser($adminUser)->allows('forceDelete', $user));
})
    ->group('policies', 'authorization');
