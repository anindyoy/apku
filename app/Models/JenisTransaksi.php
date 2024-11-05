<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JenisTransaksi extends Model
{
    /** @use HasFactory<\Database\Factories\JenisCatatanFactory> */
    use HasFactory;
    protected $table = 'jenis_transaksi';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
