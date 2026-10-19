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

    // Mencari kode voucher untuk proses klaim/order langganan.
    // Jika tidak ditemukan langsung di tabel voucher_codes, sistem akan memeriksa
    // apakah ada voucher berulang dengan label yang sesuai lalu menyelaraskannya otomatis.
    public static function temukanUntukOrder(?string $input): ?self
    {
        if (blank($input)) {
            return null;
        }

        $kode = Str::upper(trim($input));

        $kodeModel = static::query()->with('voucher')->where('code', $kode)->first();
        if ($kodeModel !== null) {
            return $kodeModel;
        }

        // Periksa voucher yang dapat dipakai berulang berdasarkan kesesuaian label.
        $voucherBerulang = Voucher::query()
            ->where('dapat_dipakai_berulang', true)
            ->whereRaw('UPPER(TRIM(label)) = ?', [$kode])
            ->first();

        if ($voucherBerulang !== null) {
            $voucherBerulang->sinkronkanKodeBerulang();

            return static::query()->with('voucher')->where('code', $kode)->first();
        }

        return null;
    }
}
