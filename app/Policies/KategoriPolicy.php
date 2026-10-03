<?php

namespace App\Policies;

use App\Models\Kategori;
use App\Models\User;

class KategoriPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function view(User $user, Kategori $kategori): bool
    {
        return $user->dapatMelihatKategori($kategori);
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function update(User $user, Kategori $kategori): bool
    {
        return $user->dapatMengelolaKategori($kategori);
    }

    public function delete(User $user, Kategori $kategori): bool
    {
        return $user->dapatMengelolaKategori($kategori);
    }
}
