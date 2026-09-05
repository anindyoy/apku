<?php

namespace App\Policies;

use App\Models\BukuKas;
use App\Models\User;

class BukuKasPolicy
{
    public function view(User $user, BukuKas $bukuKas): bool
    {
        return $user->dapatMelihatBukuKas($bukuKas);
    }

    public function update(User $user, BukuKas $bukuKas): bool
    {
        return $bukuKas->user_id === $user->id;
    }

    public function delete(User $user, BukuKas $bukuKas): bool
    {
        return $bukuKas->user_id === $user->id;
    }
}
