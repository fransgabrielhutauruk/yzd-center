<?php

namespace App\Console\Commands;

use App\Models\AkunCopytrade;
use App\Models\LaporanProfitBulanan;
use App\Notifications\MasterFeeReminderNotification;
use App\Support\ProfitPeriod;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendMasterFeeRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-master-fee-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pengingat input profit dan pembayaran Master Fee in-app kepada member (tgl 1, 7, dan 15).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa akun & tagihan Master Fee yang memerlukan pengingat...');

        $today = Carbon::now()->startOfDay();

        // Jalankan pengingat hanya pada tanggal 1, 7, dan 15
        if (!in_array($today->day, [1, 7, 15], true)) {
            $this->info("Hari ini tanggal {$today->day}, pengingat hanya berjalan pada tanggal 1, 7, dan 15. Selesai.");
            return self::SUCCESS;
        }

        $sentCount = 0;

        // 1. Pengingat Input Laporan Profit (untuk akun aktif yang periode laporan telah dibuka tapi belum lapor)
        $activeAccounts = AkunCopytrade::query()
            ->with('user')
            ->where('status', 'active')
            ->get();

        foreach ($activeAccounts as $akun) {
            $user = $akun->user;
            if (!$user) {
                continue;
            }

            $next = ProfitPeriod::nextReportablePeriod($akun, $today);
            if ($next === null) {
                continue;
            }

            $periodeLabel = $next['awal']->translatedFormat('d M Y') . ' - ' . $next['akhir']->translatedFormat('d M Y');

            // Cek idempoten: jangan kirim berulang di hari yang sama untuk akun ini
            $alreadySent = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('type', MasterFeeReminderNotification::class)
                ->whereDate('created_at', $today->toDateString())
                ->where('data', 'like', '%"akun_id":' . $akun->id . '%')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $user->notify(new MasterFeeReminderNotification(
                periodInfo: [
                    'akun_id'       => $akun->id,
                    'nomor_akun'    => $akun->nomor_akun_trading,
                    'periode_label' => $periodeLabel,
                ],
                reminderType: 'report'
            ));

            $sentCount++;
            $this->line("Pengingat input profit dikirim ke {$user->name} untuk akun #{$akun->nomor_akun_trading}.");
        }

        // 2. Pengingat Pembayaran Master Fee Belum Dibayar
        $unpaidReports = LaporanProfitBulanan::query()
            ->with('user')
            ->whereIn('status', [LaporanProfitBulanan::STATUS_BELUM_DIBAYAR, LaporanProfitBulanan::STATUS_DITOLAK])
            ->where('fee_usd', '>', 0)
            ->get();

        foreach ($unpaidReports as $laporan) {
            $user = $laporan->user;
            if (!$user) {
                continue;
            }

            // Cek idempoten: jangan kirim berulang di hari yang sama untuk laporan ini
            $alreadySent = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('type', MasterFeeReminderNotification::class)
                ->whereDate('created_at', $today->toDateString())
                ->where('data', 'like', '%"laporan_id":' . $laporan->id . '%')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $user->notify(new MasterFeeReminderNotification(
                laporan: $laporan,
                reminderType: 'payment'
            ));

            $sentCount++;
            $this->line("Pengingat pembayaran dikirim ke {$user->name} untuk laporan {$laporan->nomor}.");
        }

        $this->info("Proses selesai. Sebanyak {$sentCount} pengingat berhasil dikirimkan.");

        return self::SUCCESS;
    }
}
