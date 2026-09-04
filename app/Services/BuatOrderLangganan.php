<?php

namespace App\Services;

use App\Enums\StatusLangganan;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\User;
use App\Models\VoucherCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BuatOrderLangganan
{
    public function handle(
        User $user,
        PaketLangganan $paket,
        MetodePembayaran $metode,
        ?VoucherCode $voucherCode = null,
    ): Langganan {
        if (! $paket->is_active) {
            throw ValidationException::withMessages(['paket_langganan_id' => 'Paket langganan tidak aktif.']);
        }

        if (! $metode->is_active) {
            throw ValidationException::withMessages(['metode_pembayaran_id' => 'Metode pembayaran tidak aktif.']);
        }

        $this->pastikanVoucherDapatDipakai($voucherCode?->load('voucher'));

        [$order, $kesalahanVoucher] = DB::transaction(function () use ($user, $paket, $metode, $voucherCode): array {
            $kode = $voucherCode === null
                ? null
                : VoucherCode::query()->with('voucher')->lockForUpdate()->findOrFail($voucherCode->id);

            if ($kesalahan = $this->kesalahanVoucher($kode)) {
                return [null, $kesalahan];
            }

            $persentaseDiskon = $kode?->voucher->jumlah_diskon ?? 0;
            $nominalDiskon = intdiv($paket->harga * $persentaseDiskon, 100);

            return [Langganan::create([
                'kode_order' => $this->buatKodeOrder(),
                'user_id' => $user->id,
                'paket_langganan_id' => $paket->id,
                'metode_pembayaran_id' => $metode->id,
                'voucher_code_id' => $kode?->id,
                'label_paket' => $paket->label,
                'harga' => $paket->harga,
                'durasi_hari' => $paket->durasi_hari,
                'label_metode_pembayaran' => $metode->label,
                'detail_pembayaran' => [
                    'jenis' => $metode->jenis,
                    'nama_penyedia' => $metode->nama_penyedia,
                    'nomor_tujuan' => $metode->nomor_tujuan,
                    'nama_pemilik' => $metode->nama_pemilik,
                    'instruksi' => $metode->instruksi,
                    'gambar_qr_path' => $metode->gambar_qr_path,
                ],
                'kode_voucher' => $kode?->code,
                'persentase_diskon' => $persentaseDiskon,
                'nominal_diskon' => $nominalDiskon,
                'total_pembayaran' => $paket->harga - $nominalDiskon,
                'status' => StatusLangganan::MenungguPembayaran,
            ]), null];
        });

        if ($kesalahanVoucher !== null) {
            throw ValidationException::withMessages(['kode_voucher' => $kesalahanVoucher]);
        }

        return $order;
    }

    private function pastikanVoucherDapatDipakai(?VoucherCode $kode): void
    {
        if ($kesalahan = $this->kesalahanVoucher($kode)) {
            throw ValidationException::withMessages(['kode_voucher' => $kesalahan]);
        }
    }

    private function kesalahanVoucher(?VoucherCode $kode): ?string
    {
        if ($kode === null) {
            return null;
        }

        if (! $kode->voucher->masihBerlaku()) {
            return 'Voucher sudah kedaluwarsa.';
        }

        if (! $kode->voucher->dapat_dipakai_berulang
            && $kode->langganans()->where('status', '!=', StatusLangganan::Dibatalkan->value)->exists()) {
            return 'Kode voucher sudah pernah dipakai.';
        }

        return null;
    }

    private function buatKodeOrder(): string
    {
        do {
            $kode = 'LGN-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Langganan::where('kode_order', $kode)->exists());

        return $kode;
    }
}
