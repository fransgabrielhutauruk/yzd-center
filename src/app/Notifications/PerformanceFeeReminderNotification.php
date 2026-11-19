<?php

namespace App\Notifications;

use App\Models\LaporanProfitBulanan;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PerformanceFeeReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ?LaporanProfitBulanan $laporan = null,
        public ?array $periodInfo = null,
        public string $reminderType = 'payment'
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
        if ($this->reminderType === 'report' && $this->periodInfo) {
            $nomorAkun = $this->periodInfo['nomor_akun'] ?? '-';
            $periodeLabel = $this->periodInfo['periode_label'] ?? '';

            return [
                'type'       => 'performance_fee_input_reminder',
                'title'      => 'Pengingat Input Laporan Profit',
                'message'    => "Periode {$periodeLabel} untuk akun #{$nomorAkun} telah dibuka. Segera input laporan profit dan screenshot Myfxbook Anda.",
                'icon'       => 'calendar',
                'akun_id'    => $this->periodInfo['akun_id'] ?? null,
                'nomor_akun' => $nomorAkun,
                'url'        => '/member/performance-fee',
            ];
        }

        $feeFormatted = $this->laporan ? '$' . number_format((float) $this->laporan->fee_usd, 2) : '$0.00';
        $nomor = $this->laporan?->nomor ?? '-';
        $jatuhTempoFormatted = $this->laporan ? Carbon::parse($this->laporan->jatuh_tempo)->isoFormat('D MMMM Y') : '-';

        return [
            'type'       => 'performance_fee_reminder',
            'title'      => 'Pengingat Pembayaran Performance Fee',
            'message'    => "Performance Fee ({$nomor}) sebesar {$feeFormatted} belum dibayar. Segera lakukan transfer dan unggah bukti pembayaran.",
            'icon'       => 'alert-circle',
            'laporan_id' => $this->laporan?->id,
            'nomor'      => $nomor,
            'url'        => '/member/performance-fee',
        ];
    }
}
