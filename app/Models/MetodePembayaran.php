<?php

namespace App\Models;

use App\Models\Concerns\MembersihkanCacheOpsiSelect;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodePembayaran extends Model
{
    use HasFactory, MembersihkanCacheOpsiSelect;

    protected $fillable = [
        'label', 'jenis', 'nama_penyedia', 'nomor_tujuan', 'nama_pemilik',
        'instruksi', 'gambar_qr_path', 'is_active', 'urutan',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'urutan' => 'integer'];
    }

    public function langganans(): HasMany
    {
        return $this->hasMany(Langganan::class);
    }

    protected function cacheOpsiSelectEntitas(): string
    {
        return 'metode-pembayaran';
    }

    protected function cacheOpsiSelectPerUser(): bool
    {
        return false;
    }
}
