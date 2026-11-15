<?php

namespace App\Notifications;

use App\Models\LaporanProfitBulanan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeRejectedNotification extends Notification
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
        $feeFormatted = '$' . number_format((float) $this->laporan->fee_usd, 2);

        return [
            'type'       => 'master_fee_rejected',
            'title'      => 'Pembayaran Master Fee Ditolak',
            'message'    => "Bukti pembayaran untuk Master Fee ({$this->laporan->nomor}) sebesar {$feeFormatted} ditolak. Alasan: {$this->alasan}. Silakan unggah ulang bukti yang valid.",
            'icon'       => 'x-circle',
            'laporan_id' => $this->laporan->id,
            'nomor'      => $this->laporan->nomor,
            'alasan'     => $this->alasan,
            'url'        => '/member/master-fee',
        ];
    }
}
