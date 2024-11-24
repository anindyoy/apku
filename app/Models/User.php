<?php

namespace App\Models;

use Filament\Panel;
use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Observers\UserObserver;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
        if (date('Y-m-d', strtotime($this->email_verified_at)) == date('Y-m-d')) {
            if (!$this->buku_kas->count()) {
                $buku = BukuKas::create([
                    'user_id' => $this->id,
                    'nama_buku' => 'Kas Utama',
                    'saldo' => 0,
                ]);

                $transaksi = Transaksi::create([
                    'user_id' => $this->id,
                    'buku_kas_id' => $buku->id,
                    'tanggal' => now(),
                    'nominal' => 0,
                    'jenis' => 'Pemasukan',
                    'deskripsi' => 'Saldo pertama',
                ]);

                $kas = $transaksi->buku_kas;
                $kas->saldo += $transaksi->nominal;
                $kas->save();
            }
        }

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
            'password' => 'hashed',
        ];
    }

    public function buku_kas()
    {
        return $this->hasMany(BukuKas::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function isSuper()
    {
        return $this->role == 'super';
    }
}
