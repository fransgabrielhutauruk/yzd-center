<?php

namespace App\Notifications;

use App\Models\LaporanProfitBulanan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MasterFeeProofSubmittedNotification extends Notification
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
        $memberName = $this->laporan->user?->name ?? 'Member';

        return [
            'type'       => 'master_fee_proof_submitted',
            'title'      => 'Bukti Transfer Master Fee Baru',
            'message'    => "Member {$memberName} telah mengunggah bukti pembayaran untuk laporan {$this->laporan->nomor} ({$feeFormatted}). Menunggu verifikasi admin.",
            'icon'       => 'file-check',
            'laporan_id' => $this->laporan->id,
            'nomor'      => $this->laporan->nomor,
            'member_id'  => $this->laporan->user_id,
            'url'        => '/admin/master-fee',
        ];
    }
}
