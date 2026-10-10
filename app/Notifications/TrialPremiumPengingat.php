<?php

namespace App\Notifications;

use App\Models\TrialPremium;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Pengingat H-7 dan H-1 sebelum trial Premium berakhir.
class TrialPremiumPengingat extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TrialPremium $trial,
        public readonly int $sisaHari,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $berakhir = $this->trial->berakhir_pada?->translatedFormat('d F Y') ?? '-';

        $pesan = $this->sisaHari <= 1
            ? 'Coba gratis Premium Anda berakhir besok ('.$berakhir.'). Berlangganan sekarang agar kas dan dompet tambahan tetap dapat dikelola.'
            : 'Coba gratis Premium Anda tersisa '.$this->sisaHari.' hari (berakhir '.$berakhir.'). Pertimbangkan berlangganan agar akses Premium tetap berlanjut.';

        return (new MailMessage)
            ->subject('Trial Premium APKu tersisa '.$this->sisaHari.' hari')
            ->greeting('Halo '.$notifiable->name.',')
            ->line($pesan)
            ->line('Setelah trial berakhir, kas dan dompet ke-3 dan seterusnya hanya dapat dilihat, tidak dapat diubah, sampai Anda berlangganan.')
            ->action('Lihat Paket Langganan', url('/admin/langganans'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament',
            'title' => 'Trial Premium tersisa '.$this->sisaHari.' hari',
            'body' => 'Trial berakhir pada '.($this->trial->berakhir_pada?->toDateString() ?? '-').'. Berlangganan agar kas/dompet tambahan tetap dapat dikelola.',
            'status' => $this->sisaHari <= 1 ? 'danger' : 'warning',
            'duration' => 'persistent',
            'actions' => [],
            'trial_id' => $this->trial->id,
            'reminder_key' => 'trial-premium:'.$this->trial->id.':H-'.$this->sisaHari,
        ];
    }

    public function reminderKey(): string
    {
        return 'trial-premium:'.$this->trial->id.':H-'.$this->sisaHari;
    }
}
