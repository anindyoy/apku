<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

// Pengaturan global aktivasi fitur tabungan emas untuk pengguna tersimpan di application_settings.
class PengaturanTabunganEmas
{
    public const KUNCI = 'tabungan_emas';

    public function semua(): array
    {
        $tersimpan = ApplicationSetting::find(self::KUNCI)?->value;

        if (! is_array($tersimpan)) {
            $tersimpan = [];
        }

        return [
            'aktif' => (bool) ($tersimpan['aktif'] ?? false),
        ];
    }

    // Mengembalikan status apakah fitur tabungan emas aktif untuk pengguna tertentu atau pengguna yang sedang login.
    public function aktif(?User $user = null): bool
    {
        $target = $user ?? auth()->user();

        if ($target instanceof User && ! $target->isAdmin()) {
            return $target->tabunganEmasAktif();
        }

        return (bool) $this->semua()['aktif'];
    }

    // Menyimpan preferensi aktivasi tabungan emas milik user sendiri.
    public function simpanUser(User $user, bool $aktif): void
    {
        $user->forceFill(['tabungan_emas_aktif' => $aktif])->save();
    }

    // Menyimpan status aktivasi fitur tabungan emas oleh admin.
    public function simpan(User $user, array $data): void
    {
        abort_unless($user->isAdmin(), 403);

        $data = Validator::make($data, [
            'aktif' => ['required', 'boolean'],
        ])->validate();

        ApplicationSetting::updateOrCreate(
            ['key' => self::KUNCI],
            ['value' => ['aktif' => (bool) $data['aktif']]]
        );
    }
}
