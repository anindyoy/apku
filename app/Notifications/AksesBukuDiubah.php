<?php

namespace App\Notifications;

use App\Models\ShareBuku;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AksesBukuDiubah extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ShareBuku $share,
        private readonly bool $dicabut = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament',
            'title' => $this->dicabut ? 'Akses buku kas dicabut' : 'Akses buku kas diperbarui',
            'body' => $this->dicabut
                ? 'Akses Anda ke '.$this->share->buku_kas->nama_buku.' telah dicabut.'
                : 'Akses Anda ke '.$this->share->buku_kas->nama_buku.' kini menjadi '.ucfirst($this->share->privilege).'.',
            'status' => $this->dicabut ? 'warning' : 'info',
            'duration' => 'persistent',
            'actions' => [],
            'share_buku_id' => $this->share->id,
        ];
    }
}
