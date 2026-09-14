<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PengaturanHargaEmas
{
    public function tersimpan(): array
    {
        return ApplicationSetting::find('harga_emas')?->value ?? [];
    }

    public function semua(): array
    {
        return array_replace(config('services.harga_emas'), $this->tersimpan());
    }

    public function kunciCache(array $pengaturan): string
    {
        return 'harga-emas:buyback:'.hash('sha256', json_encode($pengaturan));
    }

    public function simpan(User $user, array $data): void
    {
        abort_unless($user->isAdmin(), 403);

        $data = Validator::make($data, [
            'url' => ['nullable', 'url:http,https', 'max:2048'],
            'source' => ['nullable', 'url:http,https', 'max:2048'],
            'timeout' => ['nullable', 'integer', 'between:1,60'],
            'cache_hours' => ['nullable', 'integer', 'between:1,168'],
        ])->validate();

        $data = array_filter($data, fn ($value): bool => filled($value));
        foreach (['timeout', 'cache_hours'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = (int) $data[$key];
            }
        }

        $kunciLama = $this->kunciCache($this->semua());
        ApplicationSetting::updateOrCreate(['key' => 'harga_emas'], ['value' => $data]);
        Cache::forget($kunciLama);
        Cache::forget($this->kunciCache($this->semua()));
    }
}
