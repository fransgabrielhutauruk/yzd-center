<?php

namespace App\Notifications;

use App\Models\TagihanMasterFee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TagihanMasterFee $tagihan
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
            'type'       => 'master_fee_verified',
            'title'      => 'Pembayaran Master Fee Terverifikasi',
            'message'    => "Pembayaran tagihan Master Fee ({$this->tagihan->nomor}) sebesar {$feeFormatted} telah diverifikasi oleh administrator dan berstatus LUNAS.",
            'icon'       => 'check-circle-2',
            'tagihan_id' => $this->tagihan->id,
            'nomor'      => $this->tagihan->nomor,
            'url'        => '/member/master-fee',
        ];
    }
}
