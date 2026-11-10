<?php

namespace App\Notifications;

use App\Models\LaporanProfitBulanan;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeIssuedNotification extends Notification
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
        $periodeFormatted = Carbon::parse($this->laporan->periode_akhir)->isoFormat('MMMM Y');
        $feeFormatted = '$' . number_format((float) $this->laporan->fee_usd, 2);

        return [
            'type'       => 'master_fee_issued',
            'title'      => 'Master Fee Diterbitkan',
            'message'    => "Laporan profit ({$this->laporan->nomor}) periode {$periodeFormatted} dengan fee {$feeFormatted} telah dibuat. Silakan lakukan pembayaran dan unggah bukti transfer.",
            'icon'       => 'receipt',
            'laporan_id' => $this->laporan->id,
            'nomor'      => $this->laporan->nomor,
            'url'        => '/member/master-fee',
        ];
    }
}
