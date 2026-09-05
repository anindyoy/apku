<?php

namespace App\Models;

use App\Notifications\AksesBukuDiubah;
use App\Services\OpsiSelectCache;
use Database\Factories\ShareBukuFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareBuku extends Model
{
    /** @use HasFactory<ShareBukuFactory> */
    use HasFactory;

    protected $table = 'share_buku';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (ShareBuku $share): void {
            $share->berlaku_mulai ??= now();
        });

        $bersihkanCache = function (ShareBuku $share): void {
            OpsiSelectCache::bersihkan('buku-kas', $share->user_id);
        };
        static::saved($bersihkanCache);
        static::deleted($bersihkanCache);

        static::updated(function (ShareBuku $share): void {
            $share->user->notify(new AksesBukuDiubah($share));
        });
        static::deleting(function (ShareBuku $share): void {
            $share->loadMissing(['user', 'buku_kas']);
            $share->user->notify(new AksesBukuDiubah($share, true));
        });
    }

    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'datetime',
            'berlaku_sampai' => 'datetime',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('berlaku_mulai')
                ->orWhere('berlaku_mulai', '<=', now()))
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('berlaku_sampai')
                ->orWhere('berlaku_sampai', '>', now()));
    }

    public function sedangAktif(): bool
    {
        return ($this->berlaku_mulai === null || $this->berlaku_mulai->lte(now()))
            && ($this->berlaku_sampai === null || $this->berlaku_sampai->gt(now()));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buku_kas()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public function pengundang()
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}
