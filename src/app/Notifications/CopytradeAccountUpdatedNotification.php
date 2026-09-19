<?php

namespace App\Notifications;

use App\Models\AkunCopytrade;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CopytradeAccountUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
        public AkunCopytrade $akun,
        public string $action = 'update'
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
        $isConfirmFollow = $this->action === 'confirm_follow';

        $title = $isConfirmFollow
            ? 'Konfirmasi Follow Master Copy Trade'
            : 'Pembaruan Akun Copy Trade';

        $message = $isConfirmFollow
            ? "{$this->user->name} mengonfirmasi telah follow master copy trade ({$this->akun->nomor_akun_trading})."
            : "{$this->user->name} telah memperbarui data akun copy trade ({$this->akun->nomor_akun_trading}) dan rekening.";

        return [
            'type'               => 'copytrade_account_updated',
            'action'             => $this->action,
            'title'              => $title,
            'message'            => $message,
            'icon'               => 'refresh-cw',
            'user_id'            => $this->user->id,
            'user_name'          => $this->user->name,
            'copytrade_id'       => $this->akun->id,
            'nomor_akun_trading' => $this->akun->nomor_akun_trading,
            'url'                => '/admin/kyc',
        ];
    }
}
