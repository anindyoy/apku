<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([UserScope::class])]
class ImportTransaksi extends Model
{
    protected $table = 'import_transaksi';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pemetaan' => 'array',
            'buat_kategori_otomatis' => 'boolean',
            'dibatalkan_at' => 'datetime',
            'mulai_diproses_at' => 'datetime',
            'selesai_diproses_at' => 'datetime',
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
}
