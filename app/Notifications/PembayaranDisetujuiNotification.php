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

// Notifikasi email dan database saat konfirmasi pembayaran disetujui admin.
class PembayaranDisetujuiNotification extends Notification implements ShouldQueue
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
            'title' => 'Langganan disetujui',
            'body' => $this->langganan->label_paket.' aktif sampai '.$this->langganan->masa_aktif_sampai?->translatedFormat('d F Y').'.',
            'status' => 'success',
            'duration' => 'persistent',
            'actions' => [],
            'langganan_id' => $this->langganan->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pembayaran Langganan Disetujui '.$this->langganan->kode_order.' — APKu')
            ->view('emails.pembayaran-disetujui', [
                'order' => $this->langganan,
                'user' => $notifiable,
                'url' => url('/admin'),
            ])
            ->text('emails.pembayaran-disetujui-text', [
                'order' => $this->langganan,
                'user' => $notifiable,
                'url' => url('/admin'),
            ]);
    }

    /**
     * Callback saat seluruh percobaan retry pada antrean telah habis.
     */
    public function failed(Throwable $exception): void
    {
        try {
            app(TelegramNotifier::class)->kirimKegagalanEmail(
                jenisEmail: 'Pembayaran Disetujui',
                kodeOrder: (string) ($this->langganan->kode_order ?? '-'),
                error: $exception->getMessage(),
            );
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi Telegram kegagalan email pembayaran disetujui', [
                'kode_order' => $this->langganan->kode_order ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
