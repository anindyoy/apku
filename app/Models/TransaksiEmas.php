<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiEmas extends Model
{
    protected $table = 'transaksi_emas';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
            'berat_gram' => 'decimal:4',
            'harga_per_gram' => 'integer',
            'biaya_tambahan' => 'integer',
            'total_rupiah' => 'integer',
        ];
    }

    public function tabunganEmas()
    {
        return $this->belongsTo(TabunganEmas::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }
}
