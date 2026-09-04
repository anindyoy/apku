<?php

namespace App\Models;

use App\Models\Concerns\MembersihkanCacheOpsiSelect;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaketLangganan extends Model
{
    use HasFactory, MembersihkanCacheOpsiSelect;

    protected $fillable = ['label', 'harga', 'durasi_hari', 'is_active'];

    protected function casts(): array
    {
        return ['harga' => 'integer', 'durasi_hari' => 'integer', 'is_active' => 'boolean'];
    }

    public function langganans(): HasMany
    {
        return $this->hasMany(Langganan::class);
    }

    protected function cacheOpsiSelectEntitas(): string
    {
        return 'paket-langganan';
    }

    protected function cacheOpsiSelectPerUser(): bool
    {
        return false;
    }
}
