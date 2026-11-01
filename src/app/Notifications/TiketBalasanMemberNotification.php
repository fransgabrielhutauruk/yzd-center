<?php

namespace App\Notifications;

use App\Models\TiketBantuan;
use App\Models\TiketBantuanBalasan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TiketBalasanMemberNotification extends Notification
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
        $member = $this->balasan->user;

        return [
            'type'        => 'tiket_balasan_member',
            'title'       => 'Balasan Baru pada Tiket',
            'message'     => "Member {$member?->name} membalas tiket [{$this->tiket->nomor}]: {$this->tiket->subjek}",
            'icon'        => 'life-buoy',
            'tiket_id'    => $this->tiket->id,
            'nomor_tiket' => $this->tiket->nomor,
            'url'         => "/admin/tickets/{$this->tiket->id}",
        ];
    }
}
