<?php

namespace App\Models;

use App\Enums\StatusTrialPremium;
use Database\Factories\TrialPremiumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $hp_normal
 * @property string|null $email_normal
 * @property \Illuminate\Support\Carbon|null $mulai_pada
 * @property \Illuminate\Support\Carbon|null $berakhir_pada
 * @property \App\Enums\StatusTrialPremium|null $status
 * @property int|null $langganan_id
 * @property \Illuminate\Support\Carbon|null $dikonversi_pada
 */
class TrialPremium extends Model
{
    use HasFactory;

    // Nama tabel tunggal sesuai migrasi.
    protected $table = 'trial_premium';

    protected $fillable = [
        'user_id',
        'hp_normal',
        'email_normal',
        'mulai_pada',
        'berakhir_pada',
        'status',
        'langganan_id',
        'dikonversi_pada',
    ];

    protected function casts(): array
    {
        return [
            'mulai_pada' => 'date',
            'berakhir_pada' => 'date',
            'status' => StatusTrialPremium::class,
            'dikonversi_pada' => 'datetime',
        ];
    }

    protected static function newFactory(): TrialPremiumFactory
    {
        return TrialPremiumFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function langganan(): BelongsTo
    {
        return $this->belongsTo(Langganan::class);
    }

    public function masihAktif(): bool
    {
        return $this->status === StatusTrialPremium::Aktif
            && $this->berakhir_pada !== null
            && $this->berakhir_pada->startOfDay()->greaterThanOrEqualTo(today());
    }

    public function sisaHari(): int
    {
        if ($this->status !== StatusTrialPremium::Aktif || $this->berakhir_pada === null) {
            return 0;
        }

        // Sisa 0 berarti berakhir hari ini.
        return max(0, (int) today()->diffInDays($this->berakhir_pada, false));
    }
}
