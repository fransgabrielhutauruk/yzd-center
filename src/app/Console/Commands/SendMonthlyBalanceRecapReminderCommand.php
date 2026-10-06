<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Console\Command;

class SendMonthlyBalanceRecapReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-balance-recap-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi in-app pengingat rekapitulasi saldo tanggal 1 kepada investor dengan akun aktif yang belum mencatatkan saldo bulan berjalan.';

    /**
     * Execute the console command.
     */
    public function handle(BalanceService $service): int
    {
        $this->info('Memeriksa investor yang memerlukan pengingat rekapitulasi saldo...');

        $users = User::query()
            ->whereIn('role', ['investor', 'affiliator'])
            ->where('status', 'active')
            ->whereHas('akunCopytrade', fn ($q) => $q->where('status', 'active'))
            ->get();

        $sentCount = 0;
        foreach ($users as $user) {
            if ($service->sendMonthlyBalanceReminderIfNeeded($user)) {
                $sentCount++;
                $this->line("Notifikasi dikirimkan ke: {$user->name} ({$user->email})");
            }
        }

        $this->info("Proses selesai. Sebanyak {$sentCount} notifikasi pengingat berhasil dikirimkan.");

        return self::SUCCESS;
    }
}
