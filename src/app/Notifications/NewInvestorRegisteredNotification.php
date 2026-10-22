<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewInvestorRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $registeredUser
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
        return [
            'type'      => 'new_investor_registered',
            'title'     => 'Pendaftaran Member Baru',
            'message'   => "Member baru {$this->registeredUser->name} (@{$this->registeredUser->username}) telah mendaftar dan menunggu verifikasi pendaftaran.",
            'icon'      => 'user-plus',
            'user_id'   => $this->registeredUser->id,
            'user_name' => $this->registeredUser->name,
            'url'       => '/admin/registrations',
        ];
    }
}
