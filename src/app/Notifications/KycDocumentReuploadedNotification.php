<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\UserDokumen;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KycDocumentReuploadedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
        public UserDokumen $dokumen
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

        return [
            'type'       => 'kyc_document_reuploaded',
            'title'      => 'Unggah Ulang Dokumen Revisi',
            'message'    => "{$this->user->name} telah mengunggah ulang dokumen revisi {$docName}.",
            'icon'       => 'file-up',
            'user_id'    => $this->user->id,
            'user_name'  => $this->user->name,
            'dokumen_id' => $this->dokumen->dokumen_id,
            'url'        => '/admin/kyc',
        ];
    }
}
