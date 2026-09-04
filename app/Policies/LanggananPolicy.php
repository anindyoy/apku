<?php

namespace App\Policies;

use App\Models\Langganan;
use App\Models\User;

class LanggananPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Langganan $model): bool
    {
        return $user->isAdmin() || $model->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function update(User $user, Langganan $model): bool
    {
        return false;
    }

    public function delete(User $user, Langganan $model): bool
    {
        return false;
    }
}
