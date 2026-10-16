<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

// Pengaturan kontak admin (nomor WhatsApp) tersimpan di application_settings.
class PengaturanKontak
{
    public const KUNCI = 'kontak';

    public function nomorWa(): ?string
    {
        $tersimpan = ApplicationSetting::find(self::KUNCI)?->value;

        if (! is_array($tersimpan) || blank($tersimpan['wa'] ?? null)) {
            return null;
        }

        return (string) $tersimpan['wa'];
    }

    // Mengembalikan URL WhatsApp siap pakai dengan teks pesan, atau null jika nomor belum diatur.
    public function urlWa(?string $teks = null): ?string
    {
        $nomor = $this->nomorWa();

        if ($nomor === null) {
            return null;
        }

        $url = 'https://wa.me/' . $nomor;

        if (filled($teks)) {
            $url .= '?text=' . rawurlencode($teks);
        }

        return $url;
    }

    public function tersimpan(): array
    {
        return ApplicationSetting::find(self::KUNCI)?->value ?? [];
    }

    public function simpan(User $user, array $data): void
    {
        abort_unless($user->isAdmin(), 403);

        $data = Validator::make($data, [
            'wa' => ['nullable', 'string', 'regex:/^[0-9]{7,15}$/', 'max:20'],
        ], [
            'wa.regex' => 'Nomor WhatsApp hanya boleh berisi angka (7–15 digit) tanpa tanda + atau spasi.',
        ])->validate();

        $data = array_filter($data, fn ($value): bool => filled($value));

        ApplicationSetting::updateOrCreate(['key' => self::KUNCI], ['value' => $data]);
    }
}
