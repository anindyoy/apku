<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([UserScope::class])]
class AuditSaldoDompet extends Model
{
    protected $table = 'audit_saldo_dompet';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bukuKas()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public function detail()
    {
        return $this->hasMany(AuditSaldoDompetDetail::class);
    }
}
