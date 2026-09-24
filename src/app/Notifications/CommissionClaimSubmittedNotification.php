<?php

namespace App\Notifications;

use App\Models\KlaimKomisi;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommissionClaimSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public KlaimKomisi $claim
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
        $affiliator = $this->claim->affiliator;
        $formattedModal = 'Rp ' . number_format((float) $this->claim->total_modal_riil, 0, ',', '.');
        $downlineCount = $this->claim->total_downline;
        $akunCount = $this->claim->total_akun;

        return [
            'type'          => 'commission_claim_submitted',
            'title'         => 'Permohonan Klaim Komisi Baru (90 Hari)',
            'message'       => "Affiliator {$affiliator?->name} mengajukan klaim komisi ({$this->claim->kode_klaim}) untuk {$downlineCount} investor ({$akunCount} akun copytrade) dengan total modal {$formattedModal}.",
            'icon'          => 'banknote',
            'claim_id'      => $this->claim->id,
            'kode_klaim'    => $this->claim->kode_klaim,
            'affiliator_id' => $affiliator?->id,
            'url'           => '/admin/commissions',
        ];
    }
}
