<?php

namespace App\Notifications;

use App\Models\AkunCopytrade;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CopytradeAccountStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AkunCopytrade $akun,
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
        $isActive = $this->status === 'active';

        $title = $isActive
            ? 'Akun Copy Trade Valid & Aktif'
            : 'Akun Copy Trade Ditolak';

        $message = $isActive
            ? "Akun investasi ({$this->akun->nomor_akun_trading}) Anda telah berhasil divalidasi dan dinyatakan aktif."
            : "Pendaftaran akun trading ({$this->akun->nomor_akun_trading}) ditolak oleh Administrator. Catatan: {$this->notes}. Silakan daftarkan kembali akun copytrade (investasi) Anda dengan data yang valid.";

        return [
            'type'               => 'copytrade_account_status',
            'title'              => $title,
            'message'            => $message,
            'icon'               => $isActive ? 'check-circle' : 'x-circle',
            'status'             => $this->status,
            'copytrade_id'       => $this->akun->id,
            'nomor_akun_trading' => $this->akun->nomor_akun_trading,
            'url'                => '/investor/investments',
        ];
    }
}
