<?php

namespace App\Notifications;

use App\Models\TagihanMasterFee;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeIssuedNotification extends Notification
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
        $periodeFormatted = Carbon::parse($this->tagihan->periode)->isoFormat('MMMM Y');
        $jatuhTempoFormatted = Carbon::parse($this->tagihan->jatuh_tempo)->isoFormat('D MMMM Y');
        $feeFormatted = '$' . number_format((float) $this->tagihan->nominal_fee, 2);

        return [
            'type'       => 'master_fee_issued',
            'title'      => 'Tagihan Master Fee Baru Diterbitkan',
            'message'    => "Tagihan Master Fee ({$this->tagihan->nomor}) periode {$periodeFormatted} sebesar {$feeFormatted} telah diterbitkan. Jatuh tempo: {$jatuhTempoFormatted}.",
            'icon'       => 'receipt',
            'tagihan_id' => $this->tagihan->id,
            'nomor'      => $this->tagihan->nomor,
            'url'        => '/member/master-fee',
        ];
    }
}
