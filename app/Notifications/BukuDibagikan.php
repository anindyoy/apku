<?php

namespace App\Notifications;

use App\Models\ShareBuku;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BukuDibagikan extends Notification
{
    use Queueable;

    public function __construct(private readonly ShareBuku $share) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament',
            'title' => 'Kas dibagikan kepada Anda',
            'body' => $this->share->buku_kas->nama_buku.' dibagikan dengan akses '.ucfirst($this->share->privilege).'.',
            'status' => 'info',
            'duration' => 'persistent',
            'actions' => [],
            'share_buku_id' => $this->share->id,
        ];
    }
}
