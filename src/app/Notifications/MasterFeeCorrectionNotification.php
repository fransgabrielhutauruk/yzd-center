<?php

namespace App\Notifications;

use App\Models\LaporanProfitBulanan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeCorrectionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LaporanProfitBulanan $laporan,
        public string $alasan
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
            'type'       => 'master_fee_correction',
            'title'      => 'Laporan Profit Perlu Koreksi',
            'message'    => "Laporan profit ({$this->laporan->nomor}) ditandai Perlu Koreksi oleh Admin. Catatan: {$this->alasan}. Silakan perbaiki data/screenshot laporan Anda.",
            'icon'       => 'alert-triangle',
            'laporan_id' => $this->laporan->id,
            'nomor'      => $this->laporan->nomor,
            'alasan'     => $this->alasan,
            'url'        => '/member/master-fee',
        ];
    }
}
