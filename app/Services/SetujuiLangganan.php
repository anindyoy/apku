<?php

namespace App\Services;

use App\Enums\StatusLangganan;
use App\Models\Langganan;
use App\Models\User;
use App\Notifications\LanggananDisetujui;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetujuiLangganan
{
    public function handle(User $admin, Langganan $langganan, ?string $catatan = null): Langganan
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException;
        }

        if ($langganan->fresh()->status === StatusLangganan::Disetujui) {
            return $langganan->fresh();
        }

        [$hasil, $baruDisetujui, $statusValid] = DB::transaction(function () use ($admin, $langganan, $catatan): array {
            $order = Langganan::query()->lockForUpdate()->findOrFail($langganan->id);

            if ($order->status === StatusLangganan::Disetujui) {
                return [$order, false, true];
            }

            if ($order->status !== StatusLangganan::MenungguVerifikasi) {
                return [$order, false, false];
            }

            $user = User::query()->lockForUpdate()->findOrFail($order->user_id);
            $mulai = $user->masa_aktif !== null && $user->masa_aktif->greaterThanOrEqualTo(today())
                ? $user->masa_aktif->copy()->addDay()
                : today();
            $sampai = $mulai->copy()->addDays($order->durasi_hari - 1);

            $user->update(['type' => 'premium', 'masa_aktif' => $sampai]);
            $order->update([
                'status' => StatusLangganan::Disetujui,
                'catatan_admin' => $catatan,
                'diverifikasi_oleh' => $admin->id,
                'tanggal_verifikasi' => now(),
                'masa_aktif_mulai' => $mulai,
                'masa_aktif_sampai' => $sampai,
            ]);

            return [$order->refresh(), true, true];
        });

        if (! $statusValid) {
            throw ValidationException::withMessages(['status' => 'Order ini tidak sedang menunggu verifikasi.']);
        }

        if ($baruDisetujui) {
            $hasil->user->notify(new LanggananDisetujui($hasil));
        }

        return $hasil;
    }
}
