<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditSaldoDompetDetail extends Model
{
    protected $table = 'audit_saldo_dompet_detail';

    protected $guarded = [];

    public function audit()
    {
        return $this->belongsTo(AuditSaldoDompet::class, 'audit_saldo_dompet_id');
    }

    public function dompet()
    {
        return $this->belongsTo(Dompet::class)->withTrashed();
    }

    public function transaksi()
    {
        return $this->hasOne(Transaksi::class);
    }
}
