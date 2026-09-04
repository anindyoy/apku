<?php

namespace App\Services;

use App\Enums\StatusLangganan;
use App\Models\Langganan;
use App\Models\User;
use App\Notifications\PembayaranLanggananDikonfirmasi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class KonfirmasiPembayaranLangganan
{
    public function handle(User $user, Langganan $langganan, string $path, ?string $catatan = null): Langganan
    {
        if ($langganan->user_id !== $user->id) {
            throw new AuthorizationException;
        }

        if (! in_array($langganan->status, [StatusLangganan::MenungguPembayaran, StatusLangganan::Ditolak], true)) {
            throw ValidationException::withMessages(['bukti_pembayaran_path' => 'Pembayaran pada order ini tidak dapat dikonfirmasi.']);
        }

        $langganan->update([
            'bukti_pembayaran_path' => $path,
            'catatan_user' => $catatan,
            'catatan_admin' => null,
            'tanggal_konfirmasi' => now(),
            'status' => StatusLangganan::MenungguVerifikasi,
        ]);

        User::admin()->each(fn (User $admin) => $admin->notify(new PembayaranLanggananDikonfirmasi($langganan)));

        return $langganan->refresh();
    }
}
