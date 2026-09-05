<?php

namespace App\Models;

use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// #[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'hp',
        'type',
        'masa_aktif',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>CustomSeeder
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'masa_aktif' => 'date',
            'password' => 'hashed',
        ];
    }

    public function masaAktifBerlaku(): bool
    {
        return $this->masa_aktif !== null
            && $this->masa_aktif->startOfDay()->greaterThanOrEqualTo(today());
    }

    public function dapatMengelolaTransaksiPada(BukuKas $bukuKas): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($bukuKas->user_id === $this->id) {
            return $bukuKas->id === $this->idBukuKasUtama()
                || $bukuKas->id === $this->idBukuKasTambahanGratis()
                || $this->masaAktifBerlaku();
        }

        return $this->hakAksesPada($bukuKas) === 'editor'
            && $this->bukuKasMasihDapatDikelolaPemilik($bukuKas);
    }

    public function dapatMelihatBukuKas(BukuKas $bukuKas): bool
    {
        return $this->isAdmin() || $bukuKas->user_id === $this->id || $this->hakAksesPada($bukuKas) !== null;
    }

    public function dapatMengelolaKolaborator(BukuKas $bukuKas): bool
    {
        return ! $this->isAdmin() && $bukuKas->user_id === $this->id;
    }

    public function hakAksesPada(BukuKas $bukuKas): ?string
    {
        if ($bukuKas->user_id === $this->id) {
            return 'owner';
        }

        return ShareBuku::query()->aktif()
            ->where('buku_kas_id', $bukuKas->id)
            ->where('user_id', $this->id)
            ->value('privilege');
    }

    private function bukuKasMasihDapatDikelolaPemilik(BukuKas $bukuKas): bool
    {
        $pemilik = $bukuKas->user;

        if ($pemilik->isAdmin() || $pemilik->masaAktifBerlaku()) {
            return true;
        }

        $bukuGratis = BukuKas::withoutGlobalScopes()
            ->where('user_id', $pemilik->id)
            ->reorder()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->limit(2)
            ->pluck('id');

        return $bukuGratis->contains($bukuKas->id);
    }

    public function dapatMembuatBukuKas(): bool
    {
        return $this->isAdmin()
            || $this->buku_kas()->count() < 2
            || $this->masaAktifBerlaku();
    }

    public function idBukuKasTambahanGratis(): ?int
    {
        return $this->buku_kas()
            ->whereKeyNot($this->idBukuKasUtama())
            ->reorder()
            ->orderBy('id')
            ->value('id');
    }

    public function idBukuKasUtama(): ?int
    {
        return $this->buku_kas()
            ->reorder()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');
    }

    public function dapatMengelolaTransaksiPadaDompet(Dompet $dompet): bool
    {
        return $dompet->user_id === $this->id
            && ($this->isAdmin()
                || $dompet->id === $this->idDompetUtama()
                || $dompet->id === $this->idDompetTambahanGratis()
                || $this->masaAktifBerlaku());
    }

    public function dapatMembuatDompet(): bool
    {
        return $this->isAdmin()
            || $this->dompet()->count() < 2
            || $this->masaAktifBerlaku();
    }

    public function idDompetUtama(): ?int
    {
        return $this->dompet()
            ->reorder()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');
    }

    public function idDompetTambahanGratis(): ?int
    {
        return $this->dompet()
            ->whereKeyNot($this->idDompetUtama())
            ->reorder()
            ->orderBy('id')
            ->value('id');
    }

    public function buku_kas()
    {
        return $this->hasMany(BukuKas::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function shareBukuDiterima(): HasMany
    {
        return $this->hasMany(ShareBuku::class);
    }

    public function bukuKasDibagikan(): BelongsToMany
    {
        return $this->belongsToMany(BukuKas::class, 'share_buku')
            ->withPivot(['privilege', 'berlaku_mulai', 'berlaku_sampai', 'invited_by_user_id'])
            ->withTimestamps();
    }

    public function dompet()
    {
        return $this->hasMany(Dompet::class);
    }

    public function utang_piutang()
    {
        return $this->hasMany(UtangPiutang::class);
    }

    public function langganans(): HasMany
    {
        return $this->hasMany(Langganan::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function scopeNotAdmin($query)
    {
        return $query->where('role', '!=', 'admin');
    }

    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }
}
