<?php

namespace App\Notifications;

use App\Models\Langganan;
use App\Services\TelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

// Notifikasi email invoice otomatis saat order langganan selesai dibuat oleh user non-admin.
class InvoiceOrderNotification extends Notification implements ShouldQueue
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
        // Hanya kirim email jika bukan admin dan alamat email terisi.
        if (method_exists($notifiable, 'isAdmin') && $notifiable->isAdmin()) {
            return [];
        }

        if (blank($notifiable->email ?? null)) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invoice Pembayaran Langganan #'.$this->langganan->kode_order.' — APKu')
            ->view('emails.invoice-order', [
                'order' => $this->langganan,
                'user' => $notifiable,
                'url' => url('/admin/langganans'),
            ])
            ->text('emails.invoice-order-text', [
                'order' => $this->langganan,
                'user' => $notifiable,
                'url' => url('/admin/langganans'),
            ]);
    }

    /**
     * Callback saat seluruh percobaan retry pada antrean telah habis.
     */
    public function failed(Throwable $exception): void
    {
        try {
            app(TelegramNotifier::class)->kirimKegagalanEmail(
                jenisEmail: 'Invoice Order',
                kodeOrder: (string) ($this->langganan->kode_order ?? '-'),
                error: $exception->getMessage(),
            );
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi Telegram kegagalan email invoice', [
                'kode_order' => $this->langganan->kode_order ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
