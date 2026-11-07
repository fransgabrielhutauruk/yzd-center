<?php

namespace App\Notifications;

use App\Models\TagihanMasterFee;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeReminderNotification extends Notification
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
        $jatuhTempoFormatted = Carbon::parse($this->tagihan->jatuh_tempo)->isoFormat('D MMMM Y');
        $feeFormatted = '$' . number_format((float) $this->tagihan->nominal_fee, 2);

        return [
            'type'       => 'master_fee_reminder',
            'title'      => 'Pengingat Pembayaran Master Fee',
            'message'    => "Tagihan Master Fee ({$this->tagihan->nomor}) sebesar {$feeFormatted} jatuh tempo pada {$jatuhTempoFormatted}. Segera transfer dan unggah bukti pembayaran.",
            'icon'       => 'alert-circle',
            'tagihan_id' => $this->tagihan->id,
            'nomor'      => $this->tagihan->nomor,
            'url'        => '/member/master-fee',
        ];
    }
}
