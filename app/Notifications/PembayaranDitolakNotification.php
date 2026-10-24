<?php

namespace App\Notifications;

use App\Models\Langganan;
use App\Services\PengaturanKontak;
use App\Services\TelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

// Notifikasi email dan database saat konfirmasi pembayaran ditolak admin.
class PembayaranDitolakNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly Langganan $langganan)
    {
        $this->queue = 'email';
        $this->afterCommit = true;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Email hanya dikirim ke user non-admin yang memiliki alamat email valid.
        $isAdmin = method_exists($notifiable, 'isAdmin') && $notifiable->isAdmin();
        if (! $isAdmin && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament',
            'title' => 'Konfirmasi pembayaran ditolak',
            'body' => $this->langganan->kode_order.': '.$this->langganan->catatan_admin,
            'status' => 'danger',
            'duration' => 'persistent',
            'actions' => [],
            'langganan_id' => $this->langganan->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urlWa = app(PengaturanKontak::class)->urlWa(
            'Halo Admin APKu, saya butuh bantuan terkait konfirmasi pembayaran order '.$this->langganan->kode_order
        );

        return (new MailMessage)
            ->subject('Konfirmasi Pembayaran Langganan Perlu Diperbaiki '.$this->langganan->kode_order.' — APKu')
            ->view('emails.pembayaran-ditolak', [
                'order' => $this->langganan,
                'user' => $notifiable,
                'url' => url('/admin/langganans'),
                'urlWa' => $urlWa,
            ])
            ->text('emails.pembayaran-ditolak-text', [
                'order' => $this->langganan,
                'user' => $notifiable,
                'url' => url('/admin/langganans'),
                'urlWa' => $urlWa,
            ]);
    }

    /**
     * Callback saat seluruh percobaan retry pada antrean telah habis.
     */
    public function failed(Throwable $exception): void
    {
        try {
            app(TelegramNotifier::class)->kirimKegagalanEmail(
                jenisEmail: 'Pembayaran Ditolak',
                kodeOrder: (string) ($this->langganan->kode_order ?? '-'),
                error: $exception->getMessage(),
            );
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi Telegram kegagalan email pembayaran ditolak', [
                'kode_order' => $this->langganan->kode_order ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
