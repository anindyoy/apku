<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VoucherCode extends Model
{
    use HasFactory;

    protected $fillable = ['voucher_id', 'code'];

    protected static function booted(): void
    {
        static::saving(function (VoucherCode $voucherCode): void {
            $voucherCode->code = Str::upper(trim($voucherCode->code));
        });
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function langganans(): HasMany
    {
        return $this->hasMany(Langganan::class);
    }
}
