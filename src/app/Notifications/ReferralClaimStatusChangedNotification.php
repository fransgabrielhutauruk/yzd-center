<?php

namespace App\Notifications;

use App\Models\KlaimReferral;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ReferralClaimStatusChangedNotification extends Notification
{
    use Queueable;

    public const MAX_CATATAN_WORDS = 10;

    public function __construct(
        public KlaimReferral $claim,
        public string $status,
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
        $isApproved = $this->status === 'approved';
        $downline = $this->claim->downline;

        $title = $isApproved
            ? 'Klaim Referral Susulan Disetujui'
            : 'Klaim Referral Susulan Ditolak';

        $catatanClean = $this->catatanAdmin
            ? preg_replace('/\s+/', ' ', trim($this->catatanAdmin))
            : null;

        $catatanPreview = $catatanClean
            ? Str::words($catatanClean, self::MAX_CATATAN_WORDS, '...')
            : null;

        $message = $isApproved
            ? "Permohonan klaim referral Anda untuk investor {$downline?->name} (@{$downline?->username}) telah disetujui Administrator."
            : ($catatanPreview
                ? "Permohonan klaim referral Anda untuk investor {$downline?->name} ditolak. Catatan: {$catatanPreview}"
                : "Permohonan klaim referral Anda untuk investor {$downline?->name} ditolak oleh Administrator.");

        return [
            'type'          => 'referral_claim_status',
            'title'         => $title,
            'message'       => $message,
            'icon'          => $isApproved ? 'check-circle' : 'x-circle',
            'status'        => $this->status,
            'claim_id'      => $this->claim->id,
            'downline_id'   => $downline?->id,
            'catatan_admin' => $this->catatanAdmin,
            'url'           => '/member/affiliates?tab=claims',
        ];
    }
}
