<?php

namespace App\Notifications;

use App\Models\Langganan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LanggananDisetujui extends Notification
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
            'title' => 'Langganan disetujui',
            'body' => $this->langganan->label_paket.' aktif sampai '.$this->langganan->masa_aktif_sampai->translatedFormat('d F Y').'.',
            'status' => 'success',
            'duration' => 'persistent',
            'actions' => [],
            'langganan_id' => $this->langganan->id,
        ];
    }
}
