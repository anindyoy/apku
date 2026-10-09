<?php

namespace App\Models;

use App\Enums\JenisSumberDana;
use App\Models\Concerns\MembersihkanCacheOpsiSelect;
use App\Models\Scopes\UserScope;
use Database\Factories\SumberDanaFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([UserScope::class])]
class SumberDana extends Model
{
    /** @use HasFactory<SumberDanaFactory> */
    use HasFactory, MembersihkanCacheOpsiSelect, SoftDeletes;

    protected $table = 'sumber_dana';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'jenis' => JenisSumberDana::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function detailAuditSaldo()
    {
        return $this->hasMany(AuditSaldoDompetDetail::class, 'sumber_dana_id');
    }

    public function getJenisLabelAttribute(): string
    {
        return $this->jenis?->label() ?? JenisSumberDana::Tunai->label();
    }

    public function mendukungHitungUang(): bool
    {
        return $this->jenis?->mendukungHitungUang() ?? false;
    }

    protected function cacheOpsiSelectEntitas(): string
    {
        return 'sumber_dana';
    }
}
