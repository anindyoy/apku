<?php

namespace App\Models;

use App\Models\User;
use App\Models\UtangPiutangDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UtangPiutang extends Model
{
    /** @use HasFactory<\Database\Factories\UtangPiutangFactory> */
    use HasFactory;
    protected $guarded = [];
    protected $table = 'utang_piutang';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function utang_piutang_detail()
    {
        return $this->hasMany(UtangPiutangDetail::class);
    }
}
