<?php

namespace App\Models;

use App\Models\User;
use App\Models\BukuKas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShareBuku extends Model
{
    /** @use HasFactory<\Database\Factories\ShareBukuFactory> */
    use HasFactory;
    protected $table = 'share_buku';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buku_kas()
    {
        return $this->belongsTo(BukuKas::class);
    }
}
