<?php

namespace App\Services;

use App\Enums\StatusLangganan;
use App\Models\Langganan;
use App\Models\User;
use App\Notifications\LanggananDitolak;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class TolakLangganan
{
    public function handle(User $admin, Langganan $langganan, string $alasan): Langganan
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException;
        }

        if ($langganan->status !== StatusLangganan::MenungguVerifikasi) {
            throw ValidationException::withMessages(['status' => 'Order ini tidak sedang menunggu verifikasi.']);
        }

        $langganan->update([
            'status' => StatusLangganan::Ditolak,
            'catatan_admin' => $alasan,
            'diverifikasi_oleh' => $admin->id,
            'tanggal_verifikasi' => now(),
        ]);
        $langganan->user->notify(new LanggananDitolak($langganan));

        return $langganan->refresh();
    }
}
