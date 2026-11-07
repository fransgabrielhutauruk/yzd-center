<?php

namespace App\Notifications;

use App\Models\TagihanMasterFee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TagihanMasterFee $tagihan,
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
        $feeFormatted = '$' . number_format((float) $this->tagihan->nominal_fee, 2);

        return [
            'type'       => 'master_fee_rejected',
            'title'      => 'Pembayaran Master Fee Ditolak',
            'message'    => "Bukti pembayaran untuk tagihan Master Fee ({$this->tagihan->nomor}) sebesar {$feeFormatted} ditolak. Alasan: {$this->alasan}. Silakan unggah ulang bukti yang valid.",
            'icon'       => 'x-circle',
            'tagihan_id' => $this->tagihan->id,
            'nomor'      => $this->tagihan->nomor,
            'alasan'     => $this->alasan,
            'url'        => '/member/master-fee',
        ];
    }
}
