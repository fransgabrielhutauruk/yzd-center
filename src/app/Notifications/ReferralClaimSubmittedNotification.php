<?php

namespace App\Notifications;

use App\Models\KlaimReferral;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReferralClaimSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public KlaimReferral $claim
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
        $downline = $this->claim->downline;

        return [
            'type'          => 'referral_claim_submitted',
            'title'         => 'Permohonan Klaim Referral Susulan',
            'message'       => "Mitra Affiliator {$affiliator?->name} (@{$affiliator?->username}) mengajukan klaim susulan untuk investor {$downline?->name} (@{$downline?->username}).",
            'icon'          => 'alert-circle',
            'claim_id'      => $this->claim->id,
            'affiliator_id' => $affiliator?->id,
            'downline_id'   => $downline?->id,
            'url'           => '/admin/referral-claims',
        ];
    }
}
