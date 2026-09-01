<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengingatPerpanjanganMasaAktif extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $jumlahHari,
        public readonly string $tanggalBerakhir,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament',
            'title' => 'Pengingat perpanjangan masa aktif',
            'body' => "Masa aktif akun Anda akan berakhir dalam {$this->jumlahHari} hari, pada {$this->tanggalBerakhir}. Silakan lakukan perpanjangan agar layanan tetap dapat digunakan.",
            'status' => $this->jumlahHari === 7 ? 'warning' : 'info',
            'duration' => 'persistent',
            'actions' => [],
            'reminder_key' => $this->reminderKey(),
        ];
    }

    public function reminderKey(): string
    {
        return "masa-aktif:{$this->tanggalBerakhir}:H-{$this->jumlahHari}";
    }
}
