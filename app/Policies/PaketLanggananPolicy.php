<?php

namespace App\Policies;

use App\Models\PaketLangganan;
use App\Models\User;

class PaketLanggananPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PaketLangganan $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PaketLangganan $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, PaketLangganan $model): bool
    {
        return false;
    }
}
