<?php

namespace App\Models;

use App\Models\UtangPiutang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UtangPiutangDetail extends Model
{
    /** @use HasFactory<\Database\Factories\UtangPiutangDetailFactory> */
    use HasFactory;
    protected $guarded = [];
    protected $table = 'utang_piutang_detail';

    public function utang_piutang()
    {
        return $this->belongsTo(UtangPiutang::class);
    }

    public function scopeKurang($query)
    {
        return $query->whereTipe('kurang');
    }

    public function scopeTambah($query)
    {
        return $query->whereTipe('tambah');
    }
}
