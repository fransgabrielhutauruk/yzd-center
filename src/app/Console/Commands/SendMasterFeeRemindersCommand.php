<?php

namespace App\Console\Commands;

use App\Models\TagihanMasterFee;
use App\Notifications\MasterFeeReminderNotification;
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
    protected $description = 'Kirim pengingat pembayaran Master Fee in-app kepada member (tgl 1, 7, atau H-1 jatuh tempo).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa tagihan Master Fee yang memerlukan pengingat...');

        $today = Carbon::now()->startOfDay();

        $tagihans = TagihanMasterFee::query()
            ->with('user')
            ->whereIn('status', [TagihanMasterFee::STATUS_BELUM_DIBAYAR, TagihanMasterFee::STATUS_DITOLAK])
            ->get();

        $sentCount = 0;

        foreach ($tagihans as $tagihan) {
            $user = $tagihan->user;
            if (!$user) {
                continue;
            }

            $jatuhTempo = Carbon::parse($tagihan->jatuh_tempo)->startOfDay();
            $isHMinus1 = $today->isSameDay($jatuhTempo->copy()->subDay());
            $isDay1 = ($today->day === 1);
            $isDay7 = ($today->day === 7);

            // Kirim hanya jika hari ini tgl 1, tgl 7, atau H-1 jatuh tempo
            if (!$isDay1 && !$isDay7 && !$isHMinus1) {
                continue;
            }

            // Cek idempoten: jangan kirim berulang di hari yang sama untuk tagihan yang sama
            $alreadySent = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('type', MasterFeeReminderNotification::class)
                ->whereDate('created_at', $today->toDateString())
                ->where('data', 'like', '%"tagihan_id":' . $tagihan->id . '%')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $user->notify(new MasterFeeReminderNotification($tagihan));
            $sentCount++;
            $this->line("Pengingat dikirim ke member {$user->name} untuk tagihan {$tagihan->nomor}.");
        }

        $this->info("Proses selesai. Sebanyak {$sentCount} pengingat berhasil dikirimkan.");

        return self::SUCCESS;
    }
}
