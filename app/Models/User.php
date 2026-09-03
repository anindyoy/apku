<?php

namespace App\Models;

use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        return $this->isSuper()
            || $bukuKas->id === $this->idBukuKasUtama()
            || $bukuKas->id === $this->idBukuKasTambahanGratis()
            || $this->masaAktifBerlaku();
    }

    public function dapatMembuatBukuKas(): bool
    {
        return $this->isSuper()
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
        return $this->isSuper()
            || $dompet->id === $this->idDompetUtama()
            || $dompet->id === $this->idDompetTambahanGratis()
            || $this->masaAktifBerlaku();
    }

    public function dapatMembuatDompet(): bool
    {
        return $this->isSuper()
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

    public function dompet()
    {
        return $this->hasMany(Dompet::class);
    }

    public function utang_piutang()
    {
        return $this->hasMany(UtangPiutang::class);
    }

    public function isSuper()
    {
        return $this->role == 'super';
    }

    public function scopeNotSuper($query)
    {
        return $query->whereNot('id', 1);
    }

    public function scopeSuper($query)
    {
        return $query->where('id', 1);
    }
}
