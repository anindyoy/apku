<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VoucherCode;

class VoucherCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, VoucherCode $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, VoucherCode $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, VoucherCode $model): bool
    {
        return $user->isAdmin() && ! $model->langganans()->exists();
    }
}
