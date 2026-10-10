<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

// Pengaturan global trial Premium (saklar dan durasi) tersimpan di application_settings.
class PengaturanTrial
{
    public const DURASI_DEFAULT = 30;

    public function semua(): array
    {
        $tersimpan = ApplicationSetting::find('trial')?->value;

        if (! is_array($tersimpan)) {
            $tersimpan = [];
        }

        return [
            'aktif' => $tersimpan['aktif'] ?? true,
            'durasi_hari' => $tersimpan['durasi_hari'] ?? self::DURASI_DEFAULT,
        ];
    }

    public function aktif(): bool
    {
        return (bool) $this->semua()['aktif'];
    }

    public function durasiHari(): int
    {
        $durasi = (int) $this->semua()['durasi_hari'];

        // Batas wajar agar salah konfigurasi tidak membuat trial abadi.
        return $durasi >= 1 && $durasi <= 90 ? $durasi : self::DURASI_DEFAULT;
    }

    public function simpan(User $user, array $data): void
    {
        abort_unless($user->isAdmin(), 403);

        $data = Validator::make($data, [
            'aktif' => ['required', 'boolean'],
            'durasi_hari' => ['required', 'integer', 'between:1,90'],
        ])->validate();

        ApplicationSetting::updateOrCreate(['key' => 'trial'], ['value' => [
            'aktif' => (bool) $data['aktif'],
            'durasi_hari' => (int) $data['durasi_hari'],
        ]]);
    }
}
