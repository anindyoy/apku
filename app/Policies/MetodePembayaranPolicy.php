<?php

namespace App\Policies;

use App\Models\MetodePembayaran;
use App\Models\User;

class MetodePembayaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, MetodePembayaran $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MetodePembayaran $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, MetodePembayaran $model): bool
    {
        return false;
    }
}
