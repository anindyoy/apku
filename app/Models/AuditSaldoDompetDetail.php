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

    public function sumberDana()
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id')->withTrashed();
    }

    // Alias kompatibilitas untuk sisa referensi refactor Dompet ke Sumber Dana.
    public function getDompetIdAttribute(): ?int
    {
        return $this->attributes['sumber_dana_id'] ?? null;
    }

    public function setDompetIdAttribute(mixed $value): void
    {
        $this->attributes['sumber_dana_id'] = $value;
    }

    public function transaksi()
    {
        return $this->hasOne(Transaksi::class);
    }
}
