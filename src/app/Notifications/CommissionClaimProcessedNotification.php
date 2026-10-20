<?php

namespace App\Notifications;

use App\Models\KlaimKomisi;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommissionClaimProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public KlaimKomisi $claim,
        public string $action, // 'approved_transferred' or 'rejected'
        public ?string $catatanAdmin = null
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
        $isApproved = $this->action === 'approved_transferred';
        $nominalTransferIdr = $this->claim->total_nominal_transfer_idr !== null
            ? (float) $this->claim->total_nominal_transfer_idr
            : ((float) ($this->claim->total_nominal_transfer ?? 0) * (float) ($this->claim->kurs_konversi_idr ?: 15000));

        $formattedTransfer = 'Rp ' . number_format($nominalTransferIdr, 0, ',', '.');

        $title = $isApproved
            ? 'Pencairan Komisi Berhasil Ditransfer'
            : 'Pengajuan Klaim Komisi Ditolak';

        $message = $isApproved
            ? "Klaim komisi {$this->claim->kode_klaim} ({$this->claim->total_downline} investor / {$this->claim->total_akun} akun copytrade) telah dicairkan sebesar {$formattedTransfer}. Bukti transfer bank telah tersedia."
            : "Klaim komisi {$this->claim->kode_klaim} ditolak oleh Administrator." . ($this->catatanAdmin ? " Alasan: {$this->catatanAdmin}" : '');

        return [
            'type'                       => 'commission_claim_processed',
            'title'                      => $title,
            'message'                    => $message,
            'icon'                       => $isApproved ? 'check-circle' : 'x-circle',
            'claim_id'                   => $this->claim->id,
            'kode_klaim'                 => $this->claim->kode_klaim,
            'status'                     => $this->action,
            'total_nominal_transfer'     => $this->claim->total_nominal_transfer,
            'total_nominal_transfer_idr' => $nominalTransferIdr,
            'url'                        => '/investor/commissions?tab=claims',
        ];
    }
}
