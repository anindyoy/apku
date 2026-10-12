<?php

namespace App\Jobs;

use App\Services\TelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Enums\StatusLangganan;
use App\Models\Langganan;

class KirimNotifTelegramPembayaran implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $langgananId;
    public $isUlang;

    public function __construct(int $langgananId, bool $isUlang = false)
    {
        $this->langgananId = $langgananId;
        $this->isUlang = $isUlang;
    }

    public function handle(): void
    {
        $langganan = Langganan::find($this->langgananId);

        if (! $langganan) {
            Log::warning('Job notifikasi Telegram pembayaran: langganan tidak ditemukan', [
                'langganan_id' => $this->langgananId,
            ]);
            return;
        }

        $token = config('services.telegram.bot_token');
        $chatIdPembayaran = config('services.telegram.chat_id_pembayaran');

        // Jika token atau chat ID kosong, catat ke log dan hentikan
        if (blank($token) || blank($chatIdPembayaran)) {
            Log::debug('Telegram notifikasi pembayaran dinonaktifkan: kredensial tidak lengkap', [
                'langganan_id' => $langganan->id,
            ]);
            return;
        }

        // Bangun judul pesan
        $judul = $this->isUlang ? 'Bukti ulang' : 'Bukti pembayaran baru';

        // Bangun detail pesan
        $user = $langganan->user;
        $namaUser = $user ? ($user->name ?? 'Tanpa nama') : 'Tanpa nama';
        $kodeOrder = $langganan->kode_order ?? '-';
        $paket = $langganan->label_paket ?? ($langganan->paketLangganan?->label ?? '-');
        $metode = $langganan->metodePembayaran?->nama ?? '-';
        $total = $langganan->total_pembayaran ?? 0;
        $waktu = $langganan->tanggal_konfirmasi ? $langganan->tanggal_konfirmasi->format('d F Y H:i') : '-';
        $voucher = $langganan->kode_voucher ?? '-';

        // Tautan admin - prioritas langsung ke order, cadangan daftar order menunggu verifikasi
        $linkAdmin = $this->buatLinkAdmin($langganan);

        // Bangun detail pesan (dengan escape HTML)
        $detail = "Kode order: $kodeOrder\n"
            . "User: $namaUser\n"
            . "Paket: $paket\n"
            . "Metode pembayaran: $metode\n"
            . "Total pembayaran: $total\n"
            . "Waktu konfirmasi: $waktu\n"
            . "Voucher: $voucher";

        $notifier = new TelegramNotifier();

        try {
            $notifier->kirimNotifikasiPembayaran($judul, $detail, $linkAdmin);
        } catch (\Throwable $e) {
            // Kegagalan job ini tidak boleh menggagalkan proses user
            Log::error('Gagal mengirim notifikasi Telegram pembayaran', [
                'langganan_id' => $langganan->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bangun tautan admin ke halaman order menunggu verifikasi.
     *
     * @param \App\Models\Langganan $langganan
     * @return string
     */
    protected function buatLinkAdmin(Langganan $langganan): string
    {
        // Gunakan cadangan: halaman daftar langganan dengan filter status menunggu verifikasi
        // Prioritas: tautan langsung ke order dapat ditambahkan jika route tersedia
        $routeCadangan = route('filament.admin.langganans.index') . '?status=' . StatusLangganan::MenungguVerifikasi->value;

        return $routeCadangan;
    }

    /**
     * hindari loop: kegagalan job ini tidak dilaporkan lagi lewat notifikasi exception Telegram.
     * Tidak ada penanganan khusus di sini - kegagalan hanya dicatat ke log.
     */
}