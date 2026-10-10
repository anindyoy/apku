<?php

namespace App\Notifications;

use App\Models\TrialPremium;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Email penutup saat trial Premium berakhir.
class TrialPremiumBerakhir extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TrialPremium $trial,
        public readonly int $jumlahTerkunci = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tambahan = $this->jumlahTerkunci > 0
            ? ' Sebanyak '.$this->jumlahTerkunci.' kas/dompet tambahan kini hanya dapat dilihat.'
            : '';

        return (new MailMessage)
            ->subject('Trial Premium APKu telah berakhir')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Coba gratis Premium Anda telah berakhir. Akun Anda kembali menjadi Reguler.'.$tambahan.' Data Anda tetap tersimpan dan tidak ada yang dihapus.')
            ->line('Berlangganan Premium untuk membuka kembali kas dan dompet tambahan serta fitur kolaborasi.')
            ->action('Lihat Paket Langganan', url('/admin/langganans'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament',
            'title' => 'Trial Premium berakhir',
            'body' => 'Akun kembali Reguler. Berlangganan untuk membuka kembali kas/dompet tambahan.',
            'status' => 'info',
            'duration' => 'persistent',
            'actions' => [],
            'trial_id' => $this->trial->id,
            'reminder_key' => 'trial-premium:'.$this->trial->id.':berakhir',
        ];
    }
}
