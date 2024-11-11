<?php

namespace App\Models;

use App\Models\User;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ScopedBy([UserScope::class])]
class JenisTransaksi extends Model
{
    /** @use HasFactory<\Database\Factories\JenisCatatanFactory> */
    use HasFactory;
    protected $table = 'jenis_transaksi';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
