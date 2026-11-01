<?php

namespace App\Console\Commands;

use App\Services\TiketBantuanService;
use Illuminate\Console\Command;

class CloseResolvedTicketsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:close-resolved-tickets {--days=7 : Jumlah hari tiket berstatus resolved sebelum ditutup otomatis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menutup otomatis tiket bantuan berstatus resolved yang telah melewati N hari tanpa aktivitas';

    /**
     * Execute the console command.
     */
    public function handle(TiketBantuanService $service): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $days = 7;
        }

        $this->info("Memeriksa tiket berstatus resolved lebih dari {$days} hari...");

        $closedCount = $service->closeResolvedTicketsAfterDays($days);

        $this->info("Berhasil menutup {$closedCount} tiket bantuan.");

        return Command::SUCCESS;
    }
}
