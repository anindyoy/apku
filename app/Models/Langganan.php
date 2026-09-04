<?php

namespace App\Models;

use App\Enums\StatusLangganan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Langganan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_order', 'user_id', 'paket_langganan_id', 'metode_pembayaran_id', 'voucher_code_id',
        'label_paket', 'harga', 'durasi_hari', 'label_metode_pembayaran',
        'detail_pembayaran', 'kode_voucher', 'persentase_diskon', 'nominal_diskon',
        'total_pembayaran', 'status', 'bukti_pembayaran_path', 'tanggal_konfirmasi',
        'catatan_user', 'catatan_admin', 'diverifikasi_oleh', 'tanggal_verifikasi',
        'masa_aktif_mulai', 'masa_aktif_sampai',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'durasi_hari' => 'integer',
            'persentase_diskon' => 'integer',
            'nominal_diskon' => 'integer',
            'total_pembayaran' => 'integer',
            'detail_pembayaran' => 'array',
            'status' => StatusLangganan::class,
            'tanggal_konfirmasi' => 'datetime',
            'tanggal_verifikasi' => 'datetime',
            'masa_aktif_mulai' => 'date',
            'masa_aktif_sampai' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paketLangganan(): BelongsTo
    {
        return $this->belongsTo(PaketLangganan::class);
    }

    public function metodePembayaran(): BelongsTo
    {
        return $this->belongsTo(MetodePembayaran::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function voucherCode(): BelongsTo
    {
        return $this->belongsTo(VoucherCode::class);
    }
}
