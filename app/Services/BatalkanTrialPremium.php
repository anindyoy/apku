<?php

namespace App\Services;

use App\Enums\StatusTrialPremium;
use App\Models\TrialPremium;
use App\Models\User;
use Illuminate\Validation\ValidationException;

// Pembatalan trial Premium oleh admin untuk akun mencurigakan.
class BatalkanTrialPremium
{
    public function handle(User $admin, TrialPremium $trial): TrialPremium
    {
        abort_unless($admin->isAdmin(), 403);

        // Ambil ulang memakai kunci primer agar status terbaru terkunci untuk admin.
        $trial = TrialPremium::query()->findOrFail($trial->getKey());

        if ($trial->status !== StatusTrialPremium::Aktif) {
            throw ValidationException::withMessages(['status' => 'Hanya trial aktif yang dapat dibatalkan.']);
        }

        $trial->update(['status' => StatusTrialPremium::Berakhir]);

        $user = $trial->user;

        // Cabut akses Premium yang berasal dari trial agar langsung kembali Reguler.
        if ($user !== null && ($user->masa_aktif === null || $user->masa_aktif->toDateString() <= $trial->berakhir_pada->toDateString())) {
            $user->update(['type' => 'reguler', 'masa_aktif' => null]);
        }

        return $trial->refresh();
    }
}
