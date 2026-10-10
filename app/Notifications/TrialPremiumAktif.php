<?php

namespace App\Notifications;

use App\Models\TrialPremium;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Sapaan H0 saat trial Premium baru diaktifkan.
class TrialPremiumAktif extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly TrialPremium $trial) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $berakhir = $this->trial->berakhir_pada?->translatedFormat('d F Y') ?? '-';

        return (new MailMessage)
            ->subject('Coba gratis Premium APKu sudah aktif')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Coba gratis Premium APKu Anda sudah aktif dan berlaku sampai '.$berakhir.'.')
            ->line('Selama trial Anda dapat menambah kas dan dompet tanpa batas kuota Reguler, mengundang kolaborator kas, serta membuat tautan kas publik.')
            ->line('Catatan: kas dan dompet ke-3 dan seterusnya tidak dapat dikelola lagi setelah trial berakhir, tetapi datanya tetap tersimpan.')
            ->action('Buka Dashboard', url('/admin'));
    }

    public function toDatabase(object $notifiable): array
    {
        $berakhir = $this->trial->berakhir_pada?->toDateString() ?? '-';

        return [
            'format' => 'filament',
            'title' => 'Coba gratis Premium aktif',
            'body' => 'Trial Premium aktif sampai '.$berakhir.'. Kas/dompet ke-3+ terkunci setelah trial berakhir.',
            'status' => 'success',
            'duration' => 'persistent',
            'actions' => [],
            'trial_id' => $this->trial->id,
            'reminder_key' => 'trial-premium:'.$this->trial->id.':aktif',
        ];
    }
}
