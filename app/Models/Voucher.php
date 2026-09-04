<?php

namespace App\Models;

use App\Models\Concerns\MembersihkanCacheOpsiSelect;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasFactory, MembersihkanCacheOpsiSelect;

    protected $fillable = ['label', 'masa_aktif', 'jumlah_diskon', 'dapat_dipakai_berulang'];

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

    protected function cacheOpsiSelectEntitas(): string
    {
        return 'voucher';
    }

    protected function cacheOpsiSelectPerUser(): bool
    {
        return false;
    }
}
