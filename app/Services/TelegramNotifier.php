<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class TelegramNotifier
{
    protected string $botToken;
    protected ?string $chatId;
    protected ?string $chatIdPembayaran;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
        $this->chatIdPembayaran = config('services.telegram.chat_id_pembayaran');
    }

    /**
     * Kirim pesan ke channel Telegram tertentu.
     *
     * @param string $chatId ID channel tujuan (kosongkan untuk menggunakan default/none)
     * @param string $text Pesan yang akan dikirim
     * @param string $parseMode Mode parse (HTML atau null)
     * @return bool
     */
    public function kirim(string $chatId, string $text, ?string $parseMode = null): bool
    {
        // Jika token kosong, diamkan (tidak kirim apa-apa)
        if (blank($this->botToken)) {
            Log::debug('Telegram notifikasi dinonaktifkan: bot_token kosong');
            return false;
        }

        // Jika chat_id tidak diberikan dan chatId default kosong, diamkan
        $targetChatId = $chatId ?? $this->chatId;
        if (blank($targetChatId)) {
            Log::debug('Telegram notifikasi dinonaktifkan: chat_id tidak tersedia');
            return false;
        }

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'chat_id' => (string) $targetChatId,
                    'text' => $text,
                    'parse_mode' => $parseMode,
                ],
            );

            if ($response->failed()) {
                Log::error('Pengiriman notifikasi ke Telegram gagal.', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'chat_id' => $targetChatId,
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Pengiriman notifikasi ke Telegram mengalami exception.', [
                'message' => $e->getMessage(),
                'chat_id' => $targetChatId,
            ]);
            return false;
        }
    }

    /**
     * Kirim pesan ke channel exception (chat_id lama).
     *
     * @param string $text Pesan exception
     * @return bool
     */
    public function kirimException(string $text): bool
    {
        return $this->kirim($this->chatId, $text);
    }

    /**
     * Kirim pesan pembayaran ke channel pembayaran (chat_id baru).
     *
     * @param string $text Pesan pembayaran
     * @return bool
     */
    public function kirimPembayaran(string $text): bool
    {
        return $this->kirim($this->chatIdPembayaran, $text, 'HTML');
    }

    /**
     * Buat dan kirim notifikasi exception Laravel ke Telegram.
     *
     * @param \Throwable $exception Exception yang akan dilaporkan
     * @return bool
     */
    public function kirimNotifikasiException(\Throwable $exception): bool
    {
        // Hanya aktif di production
        if (app()->environment('production') !== true) {
            return false;
        }

        // Hanya jika kredensial tersedia
        if (blank($this->botToken) || blank($this->chatId)) {
            return false;
        }

        $user = null;
        try {
            $user = \Illuminate\Support\Facades\Auth::user();
        } catch (\Throwable) {
            // Jika tidak ada user, lanjutkan tanpa data user
        }

        $messageLines = [
            'Error: ' . $exception->getMessage(),
            'File: ' . $exception->getFile(),
            'Line: ' . $exception->getLine(),
            'Domain: ' . config('app.url'),
        ];

        if ($user) {
            $messageLines[] = 'Username: ' . $user->name;
            $messageLines[] = 'Email: ' . $user->email;
        }

        $messageLines[] = 'Versi: #APKu';

        $message = implode("\n", $messageLines);

        // Potong pesan jika melebihi batas 4096 karakter Telegram
        $message = $this->potongPesan($message);

        return $this->kirimException($message);
    }

    /**
     * Potong pesan Telegram agar tidak melebihi 4096 karakter.
     *
     * @param string $text Pesan asli
     * @return string Pesan yang sudah dipotong
     */
    protected function potongPesan(string $text): string
    {
        $maxLength = 4096;
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        // Potong pada karakter terakhir yang aman (hindari potongan di tengah kata yang terlalu panjang)
        $text = substr($text, 0, $maxLength - 3) . '...';

        return $text;
    }

    /**
     * Kirim notifikasi pembayaran baru.
     *
     * @param string $judul Judul pesan (contoh: "Bukti pembayaran baru")
     * @param string $detail Detail pesan (kode order, nama user, dll)
     * @param string $linkAdmin Tautan ke halaman admin
     * @return bool
     */
    public function kirimNotifikasiPembayaran(string $judul, string $detail, string $linkAdmin): bool
    {
        // Hanya aktif jika ada chat_id pembayaran
        if (blank($this->chatIdPembayaran)) {
            Log::debug('Telegram notifikasi pembayaran dinonaktifkan: chat_id_pembayaran kosong');
            return false;
        }

        // Build pesan dengan escape HTML untuk nilai dinamis
        $text = $this->buatPesanPembayaran($judul, $detail, $linkAdmin);

        return $this->kirimPembayaran($text);
    }

    /**
     * Bangun pesan notifikasi pembayaran dengan parse mode HTML.
     *
     * @param string $judul Judul pesan
     * @param string $detail Detail pesan
     * @param string $linkAdmin Tautan admin
     * @return string Pesan yang sudah diformat
     */
    protected function buatPesanPembayaran(string $judul, string $detail, string $linkAdmin): string
    {
        // Escape HTML untuk nilai dinamis agar aman dalam parse mode HTML
        $judulEscape = $this->escapeHtml($judul);
        $detailEscape = $this->escapeHtml($detail);
        $linkAdminEscape = $this->escapeHtml($linkAdmin);

        $message = <<<HTML
<b>$judulEscape</b>

$detailEscape

<b>Admin:</b> <a href="$linkAdminEscape">Verifikasi order di APKu</a>
HTML;

        return $this->potongPesan($message);
    }

    /**
     * Escape HTML special characters.
     *
     * @param string $text Teks yang akan di-escape
     * @return string Teks yang sudah di-escape
     */
    protected function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}