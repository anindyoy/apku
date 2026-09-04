<?php

namespace App\Notifications;

use App\Models\Langganan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PembayaranLanggananDikonfirmasi extends Notification
{
    use Queueable;

    public function __construct(private readonly Langganan $langganan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament',
            'title' => 'Pembayaran langganan menunggu verifikasi',
            'body' => $this->langganan->kode_order.' dari '.$this->langganan->user->name.' siap diperiksa.',
            'status' => 'info',
            'duration' => 'persistent',
            'actions' => [],
            'langganan_id' => $this->langganan->id,
        ];
    }
}
