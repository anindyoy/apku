<?php

namespace App\Services;

use App\Enums\StatusTrialPremium;
use App\Models\TrialPremium;
use App\Models\User;
use App\Notifications\TrialPremiumAktif;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Layanan aktivasi coba gratis Premium 30 hari (sekali per akun/HP/email).
class MulaiTrialPremium
{
    public function __construct(private readonly PengaturanTrial $pengaturan) {}

    // Alasan penolakan pertama, atau null bila layak.
    public function alasanTidakLayak(User $user): ?string
    {
        if ($user->isAdmin()) {
            return 'Akun admin tidak dapat mengikuti coba gratis Premium.';
        }

        if (! $this->pengaturan->aktif()) {
            return 'Program coba gratis Premium sedang tidak aktif.';
        }

        // Akses lewat getAttribute agar analisis statis tidak mengeluh properti dinamis Eloquent.
        if ($user->getAttribute('email_verified_at') === null) {
            return 'Verifikasi email terlebih dahulu untuk memulai coba gratis.';
        }

        if (! filled($user->getAttribute('hp'))) {
            return 'Isi nomor HP di Akun Saya terlebih dahulu untuk memulai coba gratis.';
        }

        // Akun yang sedang Premium (termasuk sisa trial) tidak perlu trial baru.
        if ($user->masaAktifBerlaku()) {
            return 'Akun Anda sedang Premium aktif.';
        }

        if ($user->getAttribute('masa_aktif') !== null) {
            return 'Coba gratis hanya untuk akun yang belum pernah memiliki masa aktif.';
        }

        if ($user->pernahBerlanggananDisetujui()) {
            return 'Coba gratis hanya untuk akun yang belum pernah berlangganan.';
        }

        if (TrialPremium::query()->where('user_id', $user->getKey())->exists()) {
            return 'Akun ini sudah pernah mengikuti coba gratis Premium.';
        }

        $hpNormal = NormalisasiKontak::hp($user->getAttribute('hp'));
        $emailNormal = NormalisasiKontak::email($user->getAttribute('email'));

        if ($hpNormal !== null && TrialPremium::query()->where('hp_normal', $hpNormal)->exists()) {
            return 'Nomor HP ini sudah pernah dipakai untuk coba gratis Premium.';
        }

        if ($emailNormal !== null && TrialPremium::query()->where('email_normal', $emailNormal)->exists()) {
            return 'Email ini sudah pernah dipakai untuk coba gratis Premium.';
        }

        return null;
    }

    public function layak(User $user): bool
    {
        return $this->alasanTidakLayak($user) === null;
    }

    public function handle(User $user): TrialPremium
    {
        // Jangan lempar validasi di dalam transaksi: rollback ke savepoint MariaDB
        // gagal menimpa ValidationException asli dengan PDOException.
        // Pola ini mengikuti BuatOrderLangganan (kembalikan penolakan, lempar di luar).
        $penolakan = null;
        $duplikat = false;

        $trial = DB::transaction(function () use ($user, &$penolakan, &$duplikat): ?TrialPremium {
            // Kunci baris user agar klik ganda/paralel tidak membuat dua trial.
            $segar = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($alasan = $this->alasanTidakLayak($segar)) {
                $penolakan = $alasan;

                return null;
            }

            $durasi = $this->pengaturan->durasiHari();
            $mulai = today();
            // Durasi inklusif mengikuti pola persetujuan langganan (30 hari = hari ini + 29).
            $berakhir = $mulai->copy()->addDays($durasi - 1);

            $hpNormal = NormalisasiKontak::hp($segar->getAttribute('hp'));
            $emailNormal = NormalisasiKontak::email($segar->getAttribute('email'));

            try {
                $trial = TrialPremium::create([
                    'user_id' => $segar->getKey(),
                    'hp_normal' => $hpNormal,
                    'email_normal' => $emailNormal,
                    'mulai_pada' => $mulai->toDateString(),
                    'berakhir_pada' => $berakhir->toDateString(),
                    'status' => StatusTrialPremium::Aktif,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Pelanggaran unik berarti HP/email/akun sudah pernah trial (termasuk akun yang dihapus).
                $duplikat = true;

                return null;
            }

            $segar->update(['type' => 'premium', 'masa_aktif' => $berakhir->toDateString()]);

            // Cache dashboard kedaluwarsa otomatis lewat DB::listen pada penulisan users/trial_premium.
            return $trial->refresh();
        });

        if ($penolakan !== null) {
            throw ValidationException::withMessages(['trial' => $penolakan]);
        }

        if ($duplikat || $trial === null) {
            throw ValidationException::withMessages([
                'trial' => 'Akun, nomor HP, atau email ini sudah pernah mengikuti coba gratis Premium.',
            ]);
        }

        // Notifikasi sambutan dikirim setelah commit agar tidak hilang saat rollback.
        $trial->user->notify(new TrialPremiumAktif($trial));

        return $trial;
    }

    // Mengecek apakah langganan yang disetujui berasal dari user trial (untuk konversi).
    public function tandaiDikonversi(int $userId, int $langgananId): void
    {
        TrialPremium::query()
            ->where('user_id', $userId)
            ->where('status', StatusTrialPremium::Aktif)
            ->update([
                'status' => StatusTrialPremium::Dikonversi,
                'langganan_id' => $langgananId,
                'dikonversi_pada' => now(),
            ]);
    }
}
