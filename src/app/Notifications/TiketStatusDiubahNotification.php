<?php

namespace App\Notifications;

use App\Models\TiketBantuan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TiketStatusDiubahNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TiketBantuan $tiket,
        public string $statusLama,
        public string $statusBaru,
        public ?string $catatan = null
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
        $labelBaru = TiketBantuan::STATUS_LABELS[$this->statusBaru] ?? $this->statusBaru;
        $noteText = $this->catatan ? " Catatan: {$this->catatan}" : '';

        return [
            'type'        => 'tiket_status_diubah',
            'title'       => "Status Tiket [{$this->tiket->nomor}] Diperbarui",
            'message'     => "Status tiket [{$this->tiket->nomor}] kini '{$labelBaru}'.{$noteText}",
            'icon'        => 'life-buoy',
            'tiket_id'    => $this->tiket->id,
            'nomor_tiket' => $this->tiket->nomor,
            'status'      => $this->statusBaru,
            'url'         => "/member/tickets/{$this->tiket->id}",
        ];
    }
}
