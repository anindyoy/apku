<?php

use App\Models\User;

test('user factory menghasilkan email unik untuk setiap pengguna', function () {
    $users = User::factory()->count(20)->make();

    expect($users->pluck('email')->unique())->toHaveCount(20);
});
