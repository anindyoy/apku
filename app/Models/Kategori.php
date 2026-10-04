<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use App\Services\OpsiSelectCache;
use Database\Factories\KategoriFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[ScopedBy([UserScope::class])]
class Kategori extends Model
{
    /** @use HasFactory<KategoriFactory> */
    use HasFactory;

    public const TIPE = ['Pemasukan', 'Pengeluaran', 'Semua'];

    protected $table = 'kategori';

    protected $guarded = [];

    /** @var array<int, int> */
    private array $idKasSebelumDihapus = [];

    protected static function booted(): void
    {
        static::saved(fn (Kategori $kategori) => static::bersihkanCacheOpsi($kategori->idKas()));
        // Baris pivot ikut terhapus oleh database, sehingga daftar kas dicatat sebelum penghapusan.
        static::deleting(function (Kategori $kategori): void {
            $kategori->idKasSebelumDihapus = $kategori->idKas();
        });
        static::deleted(fn (Kategori $kategori) => static::bersihkanCacheOpsi($kategori->idKasSebelumDihapus));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function kas()
    {
        return $this->belongsToMany(BukuKas::class, 'kategori_kas', 'kategori_id', 'buku_kas_id');
    }

    /** @return array<int, int> */
    public function idKas(): array
    {
        return DB::table('kategori_kas')
            ->where('kategori_id', $this->id)
            ->pluck('buku_kas_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function terhubungKe(int $bukuKasId): bool
    {
        return DB::table('kategori_kas')
            ->where('kategori_id', $this->id)
            ->where('buku_kas_id', $bukuKasId)
            ->exists();
    }

    /** @param array<int, int|string> $idKas */
    public static function bersihkanCacheOpsi(array $idKas): void
    {
        foreach ($idKas as $id) {
            OpsiSelectCache::bersihkan('kategori', (int) $id);
        }
    }
}
