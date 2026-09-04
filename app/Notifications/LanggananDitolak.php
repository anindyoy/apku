<?php

namespace App\Notifications;

use App\Models\Langganan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LanggananDitolak extends Notification
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
            'title' => 'Konfirmasi pembayaran ditolak',
            'body' => $this->langganan->kode_order.': '.$this->langganan->catatan_admin,
            'status' => 'danger',
            'duration' => 'persistent',
            'actions' => [],
            'langganan_id' => $this->langganan->id,
        ];
    }
}
