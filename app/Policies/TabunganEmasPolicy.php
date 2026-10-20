<?php

namespace App\Policies;

use App\Models\TabunganEmas;
use App\Models\User;

use App\Services\PengaturanTabunganEmas;

class TabunganEmasPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isAdmin() && app(PengaturanTabunganEmas::class)->aktif($user);
    }

    public function view(User $user, TabunganEmas $tabunganEmas): bool
    {
        return app(PengaturanTabunganEmas::class)->aktif($user)
            && $user->dapatMelihatBukuKas($tabunganEmas->bukuKas);
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin() && app(PengaturanTabunganEmas::class)->aktif($user);
    }

    public function update(User $user, TabunganEmas $tabunganEmas): bool
    {
        return $tabunganEmas->bukuKas->user_id === $user->id;
    }

    public function delete(User $user, TabunganEmas $tabunganEmas): bool
    {
        return $tabunganEmas->bukuKas->user_id === $user->id
            && ! $tabunganEmas->transaksiEmas()->exists();
    }
}
