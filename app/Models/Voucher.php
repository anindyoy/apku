<?php

namespace App\Models;

use App\Models\Concerns\MembersihkanCacheOpsiSelect;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use HasFactory, MembersihkanCacheOpsiSelect;

    protected $fillable = ['label', 'masa_aktif', 'jumlah_diskon', 'dapat_dipakai_berulang'];

    protected static function booted(): void
    {
        static::saved(function (Voucher $voucher): void {
            if ($voucher->dapat_dipakai_berulang && filled($voucher->label)) {
                $voucher->sinkronkanKodeBerulang();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'masa_aktif' => 'date',
            'jumlah_diskon' => 'integer',
            'dapat_dipakai_berulang' => 'boolean',
        ];
    }

    public function codes(): HasMany
    {
        return $this->hasMany(VoucherCode::class);
    }

    public function masihBerlaku(): bool
    {
        return $this->masa_aktif === null || $this->masa_aktif->greaterThanOrEqualTo(today());
    }

    // Menyelaraskan kode voucher dengan label jika voucher dapat dipakai berulang.
    public function sinkronkanKodeBerulang(): ?VoucherCode
    {
        $kodeBersih = Str::upper(trim($this->label));
        if ($kodeBersih === '') {
            return null;
        }

        // Jika label diubah dan kode lama belum pernah dipakai di order, perbarui kodenya.
        $labelLama = Str::upper(trim((string) $this->getOriginal('label')));
        if (filled($labelLama) && $labelLama !== $kodeBersih) {
            $kodeLama = $this->codes()->where('code', $labelLama)->doesntHave('langganans')->first();
            if ($kodeLama && ! VoucherCode::query()->where('code', $kodeBersih)->exists()) {
                $kodeLama->update(['code' => $kodeBersih]);

                return $kodeLama;
            }
        }

        $kodeAda = VoucherCode::query()->where('code', $kodeBersih)->first();
        if ($kodeAda === null) {
            return $this->codes()->create(['code' => $kodeBersih]);
        }

        return $kodeAda;
    }

    protected function cacheOpsiSelectEntitas(): string
    {
        return 'voucher';
    }

    protected function cacheOpsiSelectPerUser(): bool
    {
        return false;
    }
}
