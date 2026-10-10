<?php

namespace App\Policies;

use App\Models\TrialPremium;
use App\Models\User;

// Kebijakan akses data trial: hanya admin yang boleh melihat.
class TrialPremiumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, TrialPremium $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TrialPremium $model): bool
    {
        return false;
    }

    public function delete(User $user, TrialPremium $model): bool
    {
        return false;
    }
}
