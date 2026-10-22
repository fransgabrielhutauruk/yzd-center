<?php

namespace App\Notifications;

use App\Models\UserDokumen;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KycDocumentStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public UserDokumen $dokumen,
        public string $status,
        public ?string $notes = null
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
        $docName = $this->dokumen->tipe_dokumen === 'ktp' ? 'KTP' : 'Surat Perjanjian';
        $isApproved = $this->status === 'approved';

        $title = $isApproved
            ? "Dokumen {$docName} Disetujui"
            : "Revisi Diperlukan: Dokumen {$docName}";

        $message = $isApproved
            ? "Dokumen {$docName} Anda telah diverifikasi dan dinyatakan sah oleh Administrator."
            : ($this->notes ? "Catatan: {$this->notes}" : "Mohon unggah ulang berkas {$docName} sesuai instruksi.");

        return [
            'type'       => 'kyc_document_status',
            'title'      => $title,
            'message'    => $message,
            'icon'       => $isApproved ? 'file-check' : 'alert-circle',
            'status'     => $this->status,
            'dokumen_id' => $this->dokumen->dokumen_id,
            'url'        => '/member/account',
        ];
    }
}
