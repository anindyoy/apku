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

        // Gunakan DB transaction agar penyimpanan bukti dan notifikasi admin konsisten.
        // Job Telegram dikirim setelah commit agar tidak terkirim saat transaksi dibatalkan.
        $langgananId = $langganan->id;

        $hasil = DB::transaction(function () use ($user, $langganan, $path, $catatan) {
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

            return $langganan->refresh();
        });

        // Daftarkan pengiriman job setelah commit; di dalam test yang memakai transaksi
        // pembungkus, callback dijalankan setelah transaksi dalam ini commit.
        DB::afterCommit(function () use ($langgananId) {
            dispatch(new KirimNotifTelegramPembayaran($langgananId, false));
        });

        return $hasil;
    }
}
