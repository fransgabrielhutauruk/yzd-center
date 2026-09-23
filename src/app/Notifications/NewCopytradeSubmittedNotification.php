<?php

namespace App\Notifications;

use App\Models\AkunCopytrade;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCopytradeSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $investor,
        public AkunCopytrade $akun
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
        $tierLabel = match ($this->akun->tier) {
            'tier_1' => 'Tier 1',
            'tier_2' => 'Tier 2',
            'tier_3' => 'Tier 3',
            default  => strtoupper(str_replace('_', ' ', $this->akun->tier)),
        };

        return [
            'type'               => 'new_copytrade_submitted',
            'title'              => 'Pengajuan Akun Copytrade Baru',
            'message'            => "Investor {$this->investor->name} mengajukan pendaftaran akun copytrade ({$this->akun->nomor_akun_trading}) {$tierLabel}.",
            'icon'               => 'file-check',
            'user_id'            => $this->investor->id,
            'user_name'          => $this->investor->name,
            'copytrade_id'       => $this->akun->id,
            'nomor_akun_trading' => $this->akun->nomor_akun_trading,
            'tier'               => $this->akun->tier,
            'url'                => '/admin/copytrade',
        ];
    }
}
