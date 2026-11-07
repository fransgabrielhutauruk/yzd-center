<?php

namespace App\Notifications;

use App\Models\TagihanMasterFee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeProofSubmittedNotification extends Notification
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
        $memberName = $this->tagihan->user?->name ?? 'Member';

        return [
            'type'       => 'master_fee_proof_submitted',
            'title'      => 'Bukti Transfer Master Fee Baru',
            'message'    => "Member {$memberName} telah mengunggah bukti pembayaran untuk tagihan {$this->tagihan->nomor} ({$feeFormatted}). Menunggu verifikasi admin.",
            'icon'       => 'file-check',
            'tagihan_id' => $this->tagihan->id,
            'nomor'      => $this->tagihan->nomor,
            'member_id'  => $this->tagihan->user_id,
            'url'        => '/admin/master-fee',
        ];
    }
}
