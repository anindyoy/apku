<?php

namespace App\Models;

use Database\Factories\TabunganEmasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabunganEmas extends Model
{
    /** @use HasFactory<TabunganEmasFactory> */
    use HasFactory;

    protected $table = 'tabungan_emas';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'berat_gram' => 'decimal:4',
            'harga_beli' => 'integer',
            'total_modal' => 'integer',
        ];
    }

    public function bukuKas()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public function transaksiEmas()
    {
        return $this->hasMany(TransaksiEmas::class);
    }
}
