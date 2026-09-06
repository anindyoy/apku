<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HargaEmas extends Model
{
    protected $table = 'harga_emas';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'harga_per_gram' => 'integer',
            'berlaku_pada' => 'datetime',
            'diambil_pada' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function bukuKas()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
