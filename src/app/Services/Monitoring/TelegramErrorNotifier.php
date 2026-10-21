<?php

namespace App\Services\Monitoring;

use App\Jobs\SendTelegramAlertJob;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class TelegramErrorNotifier
{
    /**
     * Daftar kelas exception yang diabaikan (bukan error 5xx sistem).
     *
     * @var array<int, class-string<Throwable>>
     */
    protected const IGNORED_EXCEPTIONS = [
        ValidationException::class,
        AuthenticationException::class,
        AuthorizationException::class,
        AccessDeniedHttpException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
        TokenMismatchException::class,
    ];

    /**
     * Kirim notifikasi error exception ke Telegram jika memenuhi syarat.
     *
     * @param array<string, mixed> $context
     */
    public function notify(Throwable $exception, array $context = [], bool $sync = false): bool
    {
        // 1. Cek apakah exception termasuk kategori yang diabaikan
        if ($this->shouldIgnore($exception)) {
            return false;
        }

        // 2. Cek apakah monitoring Telegram aktif
        if (!config('monitoring.enabled')) {
            return false;
        }

        $token = (string) config('monitoring.telegram.token');
        $chatId = (string) config('monitoring.telegram.chat_id');

        if (empty($token) || empty($chatId)) {
            return false;
        }

        // 3. Throttle per jenis error berdasarkan fingerprint unik
        $fingerprint = $context['fingerprint'] ?? $this->generateFingerprint($exception);
        $throttleMinutes = (int) config('monitoring.throttle_minutes', 10);
        $cacheKey = "monitoring:telegram:err:{$fingerprint}";

        if (!Cache::add($cacheKey, 1, now()->addMinutes($throttleMinutes))) {
            // Sudah terkirim dalam rentang window throttle
            return false;
        }

        // 4. Susun pesan HTML yang aman dari PII
        $message = $this->formatMessage($exception, $context);

        // 5. Dispatch job ke antrean atau eksekusi langsung (sync)
        if ($sync) {
            SendTelegramAlertJob::dispatchSync($message);
        } else {
            SendTelegramAlertJob::dispatch($message);
        }

        return true;
    }

    /**
     * Kirim notifikasi kegagalan antrean job (Queue failing).
     */
    public function notifyJobFailure(string $jobName, Throwable $exception, bool $sync = false): bool
    {
        return $this->notify($exception, [
            'type'        => 'Queue Job Failure',
            'job'         => $jobName,
            'fingerprint' => sha1('job_fail|' . $jobName . '|' . $exception->getMessage()),
        ], $sync);
    }

    /**
     * Kirim notifikasi kegagalan tugas terjadwal (Scheduler failure).
     */
    public function notifyScheduleFailure(string $commandName, Throwable $exception, bool $sync = false): bool
    {
        return $this->notify($exception, [
            'type'        => 'Scheduler Failure',
            'command'     => $commandName,
            'fingerprint' => sha1('schedule_fail|' . $commandName . '|' . $exception->getMessage()),
        ], $sync);
    }

    /**
     * Cek apakah exception harus diabaikan dari notifikasi Telegram.
     */
    public function shouldIgnore(Throwable $exception): bool
    {
        foreach (self::IGNORED_EXCEPTIONS as $ignored) {
            if ($exception instanceof $ignored) {
                return true;
            }
        }

        // Abaikan exception HTTP dengan status kode di bawah 500
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            return true;
        }

        return false;
    }

    /**
     * Buat fingerprint unik berdasarkan kelas error, berkas, dan nomor baris.
     */
    protected function generateFingerprint(Throwable $exception): string
    {
        return sha1(get_class($exception) . '|' . $exception->getFile() . '|' . $exception->getLine());
    }

    /**
     * Susun pesan HTML Telegram yang ringkas, informatif, dan aman tanpa data sensitif.
     *
     * @param array<string, mixed> $context
     */
    protected function formatMessage(Throwable $exception, array $context): string
    {
        $env = strtoupper((string) config('app.env', 'PRODUCTION'));
        $title = $context['type'] ?? 'Server Error Alert';

        $exceptionClass = htmlspecialchars(class_basename($exception));
        $cleanMessage = Str::limit(trim($exception->getMessage()) ?: 'Tidak ada pesan spesifik', 300);
        $escapedMessage = htmlspecialchars($cleanMessage);

        // Path relatif berkas
        $filePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $exception->getFile());
        $location = htmlspecialchars($filePath . ':' . $exception->getLine());

        // Konteks Rute / Permintaan HTTP
        $routeInfo = 'CLI / Background';
        $userInfo = 'Tamu / Sistem';

        // Deteksi Pengguna Aktif (baik di web maupun testing console)
        $authUser = auth()->user() ?? (request() ? request()->user() : null);
        if ($authUser) {
            $role = $authUser->role ?? 'user';
            $userInfo = 'User #' . $authUser->id . ' (' . htmlspecialchars((string) $role) . ')';
        }

        if (app()->runningInConsole()) {
            if (isset($context['job'])) {
                $routeInfo = 'Job: ' . htmlspecialchars($context['job']);
            } elseif (isset($context['command'])) {
                $routeInfo = 'Schedule: ' . htmlspecialchars($context['command']);
            }
        } else {
            /** @var Request|null $request */
            $request = request();
            if ($request) {
                $routeInfo = htmlspecialchars($request->method() . ' ' . $request->path());
            }
        }

        $time = now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s T');

        $lines = [
            "🚨 <b>[{$env}] {$title}</b>",
            '',
            "<b>Error:</b> <code>{$exceptionClass}</code>",
            "<b>Pesan:</b> {$escapedMessage}",
            "<b>Lokasi:</b> <code>{$location}</code>",
            "<b>Trigger:</b> <code>{$routeInfo}</code>",
            "<b>Pengguna:</b> {$userInfo}",
            "<b>Waktu:</b> {$time}",
        ];

        return implode("\n", $lines);
    }
}
