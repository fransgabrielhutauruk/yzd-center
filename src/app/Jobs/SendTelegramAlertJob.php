<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTelegramAlertJob implements ShouldQueue
{
    use Queueable;

    /**
     * Jumlah percobaan maksimal eksekusi job.
     */
    public int $tries = 2;

    /**
     * Waktu batas timeout job (detik).
     */
    public int $timeout = 10;

    /**
     * Buat instance job notifikasi baru.
     */
    public function __construct(
        public string $message
    ) {}

    /**
     * Eksekusi job untuk mengirimkan pesan ke bot Telegram.
     */
    public function handle(): void
    {
        $token = (string) config('monitoring.telegram.token');
        $chatId = (string) config('monitoring.telegram.chat_id');

        if (empty($token) || empty($chatId)) {
            return;
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 500, throw: false)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'                  => $chatId,
                    'text'                     => $this->message,
                    'parse_mode'               => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->failed()) {
                Log::warning('Gagal mengirim Telegram alert: HTTP ' . $response->status() . ' - ' . $response->body());
            }
        } catch (Throwable $e) {
            // Gagalkan secara diam-diam (silent fail) untuk menghindari loop tak berujung
            Log::warning('Exception saat mengirim Telegram alert: ' . $e->getMessage());
        }
    }

    /**
     * Tangani kegagalan akhir job.
     */
    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            Log::warning('SendTelegramAlertJob gagal total: ' . $exception->getMessage());
        }
    }
}
