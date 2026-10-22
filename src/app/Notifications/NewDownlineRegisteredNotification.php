<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewDownlineRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $downline,
        public User $affiliator
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
            'type'        => 'new_downline_registered',
            'title'       => 'Downline Baru Terhubung',
            'message'     => "Member {$this->downline->name} (@{$this->downline->username}) telah bergabung ke jaringan kemitraan Anda.",
            'icon'        => 'user-plus',
            'downline_id' => $this->downline->id,
            'url'         => '/member/affiliates',
        ];
    }
}
