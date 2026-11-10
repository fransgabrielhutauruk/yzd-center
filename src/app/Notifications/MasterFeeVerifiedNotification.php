<?php

namespace App\Notifications;

use App\Models\LaporanProfitBulanan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LaporanProfitBulanan $laporan
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
            'type'       => 'master_fee_verified',
            'title'      => 'Pembayaran Master Fee Terverifikasi',
            'message'    => "Pembayaran Master Fee ({$this->laporan->nomor}) sebesar {$feeFormatted} telah diverifikasi oleh administrator dan berstatus LUNAS.",
            'icon'       => 'check-circle-2',
            'laporan_id' => $this->laporan->id,
            'nomor'      => $this->laporan->nomor,
            'url'        => '/member/master-fee',
        ];
    }
}
