<?php

namespace App\Notifications;

use App\Models\TiketBantuan;
use App\Models\TiketBantuanBalasan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TiketBalasanAdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TiketBantuan $tiket,
        public TiketBantuanBalasan $balasan
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
        return [
            'type'        => 'tiket_balasan_admin',
            'title'       => 'Tanggapan Baru dari Admin',
            'message'     => "Admin telah membalas tiket bantuan Anda [{$this->tiket->nomor}]: {$this->tiket->subjek}",
            'icon'        => 'life-buoy',
            'tiket_id'    => $this->tiket->id,
            'nomor_tiket' => $this->tiket->nomor,
            'url'         => "/member/tickets/{$this->tiket->id}",
        ];
    }
}
