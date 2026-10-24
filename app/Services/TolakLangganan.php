<?php

namespace App\Services;

use App\Enums\StatusLangganan;
use App\Models\Langganan;
use App\Models\User;
use App\Notifications\PembayaranDitolakNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TolakLangganan
{
    public function handle(User $admin, Langganan $langganan, string $alasan): Langganan
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException;
        }

        [$hasil, $baruDitolak] = DB::transaction(function () use ($admin, $langganan, $alasan): array {
            $order = Langganan::query()->lockForUpdate()->findOrFail($langganan->id);

            if ($order->status !== StatusLangganan::MenungguVerifikasi) {
                return [$order, false];
            }

            $order->update([
                'status' => StatusLangganan::Ditolak,
                'catatan_admin' => $alasan,
                'diverifikasi_oleh' => $admin->id,
                'tanggal_verifikasi' => now(),
            ]);

            return [$order->refresh(), true];
        });

        if (! $baruDitolak) {
            throw ValidationException::withMessages(['status' => 'Order ini tidak sedang menunggu verifikasi.']);
        }

        $hasil->user->notify(new PembayaranDitolakNotification($hasil));

        return $hasil;
    }
}
