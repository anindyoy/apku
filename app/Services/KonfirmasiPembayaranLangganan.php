<?php

namespace App\Services;

use App\Enums\StatusLangganan;
use App\Jobs\KirimNotifTelegramPembayaran;
use App\Models\Langganan;
use App\Models\User;
use App\Notifications\PembayaranLanggananDikonfirmasi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

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

        // Gunakan DB transaction untuk memastikan job Telegram.dispatch setelah commit
        return DB::transaction(function () use ($user, $langganan, $path, $catatan) {
            // Simpan data bukti pembayaran
            $langganan->update([
                'bukti_pembayaran_path' => $path,
                'catatan_user' => $catatan,
                'catatan_admin' => null,
                'tanggal_konfirmasi' => now(),
                'status' => StatusLangganan::MenungguVerifikasi,
            ]);

            // Kirim notifikasi ke admin via database
            User::admin()->each(fn (User $admin) => $admin->notify(new PembayaranLanggananDikonfirmasi($langganan)));

            // Dispatch job notifikasi Telegram setelah commit transaksi
            // Gunakan dispatch dan pastikan setelah commit
            Queue::afterCommit(function () use ($langganan) {
                dispatch(new KirimNotifTelegramPembayaran($langganan->id, false));
            });

            return $langganan->refresh();
        });
    }
}
