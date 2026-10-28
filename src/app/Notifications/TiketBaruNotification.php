<?php

namespace App\Notifications;

use App\Models\TiketBantuan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TiketBaruNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TiketBantuan $tiket
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $member = $this->tiket->user;

        return [
            'type'        => 'tiket_baru',
            'title'       => 'Tiket Bantuan Baru',
            'message'     => "Member {$member?->name} membuat tiket baru [{$this->tiket->nomor}]: {$this->tiket->subjek}",
            'icon'        => 'life-buoy',
            'tiket_id'    => $this->tiket->id,
            'nomor_tiket' => $this->tiket->nomor,
            'kategori'    => $this->tiket->kategori,
            'url'         => "/admin/tickets/{$this->tiket->id}",
        ];
    }
}
