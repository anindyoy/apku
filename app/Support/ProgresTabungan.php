<?php

namespace App\Support;

use App\Models\BukuKas;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ProgresTabungan
{
    public const STATUS_TERCAPAI = 'tercapai';
    public const STATUS_TERLEWAT = 'terlewat';
    public const STATUS_MENDEKATI = 'mendekati';
    public const STATUS_BERJALAN = 'berjalan';

    public function __construct(
        public readonly bool $aktif,
        public readonly ?int $persen,
        public readonly ?int $barPersen,
        public readonly ?string $status,
        public readonly ?string $labelStatus,
        public readonly ?int $sisaHari,
        public readonly ?string $tanggalDeadlineFormatted,
    ) {}

    /**
     * Menghitung progres dan status kas tabungan berdasarkan saldo, target, tenggat, dan status premium.
     */
    public static function dari(BukuKas $kas, ?CarbonInterface $hariIni = null, ?bool $isPremium = null): self
    {
        $hariIniCarbon = $hariIni ? Carbon::instance($hariIni)->startOfDay() : today()->startOfDay();
        $premiumAktif = $isPremium ?? $kas->user?->masaAktifBerlaku() ?? false;

        // Jika bukan kas tabungan atau premium tidak aktif, kas diperlakukan seperti kas biasa.
        if (! $kas->is_tabungan || ! $premiumAktif) {
            return new self(
                aktif: false,
                persen: null,
                barPersen: null,
                status: null,
                labelStatus: null,
                sisaHari: null,
                tanggalDeadlineFormatted: null,
            );
        }

        $goal = $kas->goal !== null ? (int) $kas->goal : null;
        $saldo = (int) $kas->saldo;
        $persen = null;
        $barPersen = null;

        // Persentase hanya dihitung jika goal ditentukan dan lebih besar dari 0.
        if ($goal !== null && $goal > 0) {
            $persen = (int) floor(max($saldo, 0) / $goal * 100);
            $barPersen = min(100, max(0, $persen));
        }

        $deadline = $kas->tanggal_goal ? Carbon::parse($kas->tanggal_goal)->startOfDay() : null;
        $tanggalDeadlineFormatted = $deadline?->translatedFormat('d M Y');

        $sisaHari = null;
        if ($deadline !== null) {
            $sisaHari = (int) $hariIniCarbon->diffInDays($deadline, false);
        }

        // Penentuan status dengan urutan prioritas: Tercapai > Terlewat > Mendekati > Berjalan
        if ($goal !== null && $saldo >= $goal) {
            $status = self::STATUS_TERCAPAI;
            $labelStatus = 'Tercapai';
        } elseif ($deadline !== null && $deadline->lessThan($hariIniCarbon)) {
            $status = self::STATUS_TERLEWAT;
            $labelStatus = 'Terlewat (' . $tanggalDeadlineFormatted . ')';
        } elseif ($deadline !== null && $deadline->greaterThanOrEqualTo($hariIniCarbon) && $deadline->lessThan($hariIniCarbon->copy()->addMonthNoOverflow())) {
            $status = self::STATUS_MENDEKATI;
            $labelStatus = $sisaHari === 0 ? 'Berakhir hari ini' : "{$sisaHari} hari lagi";
        } else {
            $status = self::STATUS_BERJALAN;
            $labelStatus = 'Berjalan';
        }

        return new self(
            aktif: true,
            persen: $persen,
            barPersen: $barPersen,
            status: $status,
            labelStatus: $labelStatus,
            sisaHari: $sisaHari,
            tanggalDeadlineFormatted: $tanggalDeadlineFormatted,
        );
    }
}
