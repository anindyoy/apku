<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BukuKas extends Model
{
    /** @use HasFactory<\Database\Factories\BukuKasFactory> */
    use HasFactory;
    protected $table = 'buku_kas';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
