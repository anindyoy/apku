<?php

namespace App\Models;

use App\Models\User;
use App\Models\BukuKas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaksi extends Model
{
    /** @use HasFactory<\Database\Factories\TransaksiFactory> */
    use HasFactory;
    protected $table = 'transaksi';

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function buku_kas(){
        return $this->belongsTo(BukuKas::class);
    }

    public function tujuan_buku_tabungan(){
        return $this->belongsTo(BukuKas::class);
    }
}
