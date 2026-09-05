<?php

namespace App\Policies;

use App\Models\ShareBuku;
use App\Models\User;

class ShareBukuPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function view(User $user, ShareBuku $share): bool
    {
        return $this->update($user, $share);
    }

    public function update(User $user, ShareBuku $share): bool
    {
        return $share->buku_kas()->withoutGlobalScopes()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, ShareBuku $share): bool
    {
        return $this->update($user, $share);
    }
}
