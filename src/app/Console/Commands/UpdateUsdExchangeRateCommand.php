<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class UpdateUsdExchangeRateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-usd-exchange-rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ambil nilai tukar pasar tengah resmi USD ke IDR dari Frankfurter API dan perbarui cache serta tabel konfigurasi sistem.';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateService $service): int
    {
        $this->info('Mengambil nilai kurs USD ke IDR dari Frankfurter API...');

        $result = $service->fetchAndStoreRate();

        if ($result['is_live']) {
            $this->info(sprintf(
                'Berhasil memperbarui kurs USD ke IDR: Rp %s (Pembaruan: %s)',
                number_format($result['rate'], 0, ',', '.'),
                $result['updated_at']
            ));

            return self::SUCCESS;
        }

        $this->warn(sprintf(
            'Koneksi API gagal, menggunakan kurs cadangan: Rp %s (Sumber: %s)',
            number_format($result['rate'], 0, ',', '.'),
            $result['source']
        ));

        return self::SUCCESS;
    }
}
