<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MonthlyBalanceRecapReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $month,
        public int $year
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
        $namaBulan = Carbon::createFromDate($this->year, $this->month, 1)->locale('id')->translatedFormat('F Y');

        return [
            'type'    => 'balance_recap_reminder',
            'title'   => 'Waktunya Rekapitulasi Saldo Tanggal 1!',
            'message' => "Buka aplikasi MT4 Anda, periksa saldo terkini, lalu input saldo tanggal 1 ({$namaBulan}) agar akumulasi keuntungan dan progres BEP akun Anda terdata secara akurat.",
            'icon'    => 'calendar',
            'month'   => $this->month,
            'year'    => $this->year,
            'url'     => '/investor/investments#kinerja-dan-riwayat-akun',
        ];
    }
}
