<?php

namespace App\Console\Commands;

use App\Jobs\SendTelegramAlertJob;
use Illuminate\Console\Command;
use Throwable;

class MonitoringTestCommand extends Command
{
    /**
     * Nama dan signature perintah console.
     *
     * @var string
     */
    protected $signature = 'monitoring:test 
                            {--telegram : Hanya uji notifikasi Telegram}
                            {--sentry : Hanya uji pengiriman event Sentry}';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Kirim pesan uji coba ke bot Telegram dan event pengujian ke Sentry';

    /**
     * Eksekusi perintah console.
     */
    public function handle(): int
    {
        $this->info('=====================================================');
        $this->info('  YZD Center - Pengujian Pemantauan Error Real-Time  ');
        $this->info('=====================================================');
        $this->newLine();

        $testTelegram = !$this->option('sentry') || $this->option('telegram');
        $testSentry = !$this->option('telegram') || $this->option('sentry');

        $hasError = false;

        // 1. Pengujian Notifikasi Telegram
        if ($testTelegram) {
            $this->components->info('Menguji Notifikasi Bot Telegram...');

            $token = (string) config('monitoring.telegram.token');
            $chatId = (string) config('monitoring.telegram.chat_id');

            if (empty($token) || empty($chatId)) {
                $this->components->warn('Kredensial Telegram belum diatur di .env (MONITORING_TELEGRAM_TOKEN / MONITORING_TELEGRAM_CHAT_ID).');
                $this->line('  -> Telegram error notifier saat ini berjalan dalam mode NO-OP (tidak mengirim pesan).');
            } else {
                $maskedToken = substr($token, 0, 6) . '...' . substr($token, -4);
                $this->line("  -> Token   : {$maskedToken}");
                $this->line("  -> Chat ID : {$chatId}");

                try {
                    $env = strtoupper((string) config('app.env', 'LOCAL'));
                    $time = now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s T');

                    $orgSlug = config('monitoring.sentry_org_slug');
                    $sentryUrl = !empty($orgSlug)
                        ? "https://{$orgSlug}.sentry.io/issues/"
                        : 'https://sentry.io/issues/';

                    $testMessage = implode("\n", [
                        "✅ <b>[{$env}] Uji Coba Monitoring YZD Center</b>",
                        '',
                        'Koneksi bot Telegram berhasil terhubung.',
                        'Notifikasi error kritis & peringatan sistem siap dikirim secara real-time.',
                        '',
                        "<b>Waktu Uji:</b> {$time}",
                        '<b>Trigger:</b> <code>php artisan monitoring:test</code>',
                        '',
                        "🔍 <a href=\"{$sentryUrl}\"><b>Buka Dasbor Sentry &rarr;</b></a>",
                    ]);

                    SendTelegramAlertJob::dispatchSync($testMessage);

                    $this->components->info('Pesan uji coba Telegram berhasil dikirim! Silakan periksa chat bot Anda.');
                } catch (Throwable $e) {
                    $hasError = true;
                    $this->components->error('Gagal mengirim pesan uji coba ke Telegram: ' . $e->getMessage());
                }
            }
            $this->newLine();
        }

        // 2. Pengujian Sentry
        if ($testSentry) {
            $this->components->info('Menguji Integrasi Sentry...');

            $dsn = (string) config('sentry.dsn');

            if (empty($dsn)) {
                $this->components->warn('SENTRY_LARAVEL_DSN belum diatur di .env.');
                $this->line('  -> Sentry SDK saat ini berjalan dalam mode NO-OP (tidak mengirim event).');
            } else {
                $this->line("  -> Sentry DSN : {$dsn}");

                try {
                    $exitCode = $this->call('sentry:test');
                    if ($exitCode === 0) {
                        $this->components->info('Event uji coba Sentry berhasil dikirim!');
                    } else {
                        $hasError = true;
                        $this->components->error('Sentry menolak pengiriman event. Periksa detail alasan penolakan SDK di atas.');
                    }
                } catch (Throwable $e) {
                    $hasError = true;
                    $this->components->error('Gagal mengeksekusi pengujian Sentry: ' . $e->getMessage());
                }
            }
            $this->newLine();
        }

        $this->info('Pemeriksaan selesai.');

        return $hasError ? Command::FAILURE : Command::SUCCESS;
    }
}
