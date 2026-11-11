<?php

namespace App\Models;

use App\Models\Concerns\MembersihkanCacheOpsiSelect;
use App\Models\Scopes\UserScope;
use App\Services\OpsiSelectCache;
use Database\Factories\BukuKasFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([UserScope::class])]
class BukuKas extends Model
{
    /** @use HasFactory<BukuKasFactory> */
    use HasFactory, MembersihkanCacheOpsiSelect;

    protected $table = 'buku_kas';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_tabungan' => 'boolean',
            'pernah_dikolaborasikan' => 'boolean',
            'goal' => 'integer',
            'tanggal_goal' => 'date',
        ];
    }

    /** Scope untuk mengurutkan kas biasa terlebih dahulu, kemudian kas tabungan, dan urut nama di setiap kelompok. */
    public function scopeUrutanTabungan(Builder $query): Builder
    {
        return $query->reorder()
            ->orderBy('is_tabungan')
            ->orderBy('nama_buku');
    }

    /** Mendapatkan kalkulasi progres dan status tabungan. */
    public function progresTabungan(?\Carbon\CarbonInterface $hariIni = null, ?bool $isPremium = null): \App\Support\ProgresTabungan
    {
        return \App\Support\ProgresTabungan::dari($this, $hariIni, $isPremium);
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderByRaw("CASE WHEN nama_buku = 'Kas Utama' THEN 1 ELSE 2 END")
                ->orderBy('nama_buku');
        });

        $bersihkanKolaborator = function (BukuKas $kas): void {
            $kas->bersihkanCacheKolaborator();
        };

        static::saved($bersihkanKolaborator);
        static::deleted($bersihkanKolaborator);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function kategori()
    {
        return $this->belongsToMany(Kategori::class, 'kategori_kas', 'buku_kas_id', 'kategori_id');
    }

    public function tabunganEmas()
    {
        return $this->hasMany(TabunganEmas::class);
    }

    public function shares()
    {
        return $this->hasMany(ShareBuku::class);
    }

    public function anggota()
    {
        return $this->belongsToMany(User::class, 'share_buku')
            ->withPivot(['privilege', 'berlaku_mulai', 'berlaku_sampai', 'invited_by_user_id'])
            ->withTimestamps();
    }

    public static function getRandomBukuKas($user_id)
    {
        return self::where('user_id', $user_id)
            ->inRandomOrder();
    }

    /**
     * Opsi kas yang kategorinya dapat dikelola pengguna, terformat dengan label kas bersama jika ada.
     *
     * @return array<int, array{label: string, user_id: int}>
     */
    public static function opsiKasKelolaKategori(User $user): array
    {
        return OpsiSelectCache::ingat('buku-kas', function () use ($user): array {
            return static::query()->urutanTabungan()->with('user:id,name')->get()
                ->filter(fn (BukuKas $kas): bool => $user->dapatMengelolaKategoriPada($kas))
                ->mapWithKeys(fn (BukuKas $kas): array => [
                    $kas->id => [
                        'label' => $kas->user_id === $user->id
                            ? $kas->nama_buku
                            : $kas->nama_buku.' (kas bersama '.($kas->user?->name ?? '-').')',
                        'user_id' => $kas->user_id,
                    ],
                ])
                ->all();
        }, $user->id, 'kelola-kategori');
    }

    /** Membersihkan cache opsi kas untuk seluruh kolaborator pada kas ini. */
    public function bersihkanCacheKolaborator(): void
    {
        if (! $this->pernah_dikolaborasikan) {
            return;
        }

        $idAnggota = $this->anggota()->pluck('users.id')->all();
        foreach ($idAnggota as $userId) {
            OpsiSelectCache::bersihkan('buku-kas', (int) $userId);
        }
    }

    protected function cacheOpsiSelectEntitas(): string
    {
        return 'buku-kas';
    }
}
