<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramAlertJob;
use App\Models\User;
use App\Services\Monitoring\TelegramErrorNotifier;
use App\Support\Monitoring\SentryScrubber;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Sentry\Event;
use Sentry\UserDataBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ErrorMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_500_exception_dispatches_telegram_alert_job_when_monitoring_enabled(): void
    {
        Queue::fake([SendTelegramAlertJob::class]);

        Config::set('monitoring.telegram.token', 'test-bot-token');
        Config::set('monitoring.telegram.chat_id', '99887766');
        Config::set('monitoring.enabled', true);

        $notifier = app(TelegramErrorNotifier::class);
        $exception = new RuntimeException('Database connection failed unexpectedly');

        $result = $notifier->notify($exception);

        $this->assertTrue($result);
        Queue::assertPushed(SendTelegramAlertJob::class, function (SendTelegramAlertJob $job) {
            return str_contains($job->message, 'Database connection failed unexpectedly')
                && str_contains($job->message, 'RuntimeException');
        });
    }

    public function test_telegram_alert_is_throttled_for_same_error_within_window(): void
    {
        Queue::fake([SendTelegramAlertJob::class]);

        Config::set('monitoring.telegram.token', 'test-bot-token');
        Config::set('monitoring.telegram.chat_id', '99887766');
        Config::set('monitoring.throttle_minutes', 10);
        Config::set('monitoring.enabled', true);

        $notifier = app(TelegramErrorNotifier::class);
        $exception = new RuntimeException('Repeated fatal service error');

        // Panggilan pertama harus terkirim
        $firstResult = $notifier->notify($exception);
        $this->assertTrue($firstResult);

        // Panggilan kedua untuk error yang sama harus di-throttle (tidak terkirim lagi)
        $secondResult = $notifier->notify($exception);
        $this->assertFalse($secondResult);

        Queue::assertPushed(SendTelegramAlertJob::class, 1);
    }

    public function test_client_errors_and_validation_exceptions_are_ignored(): void
    {
        Queue::fake([SendTelegramAlertJob::class]);

        Config::set('monitoring.telegram.token', 'test-bot-token');
        Config::set('monitoring.telegram.chat_id', '99887766');
        Config::set('monitoring.enabled', true);

        $notifier = app(TelegramErrorNotifier::class);

        // 1. ValidationException
        $validationEx = ValidationException::withMessages(['email' => ['Format email tidak valid']]);
        $this->assertFalse($notifier->notify($validationEx));

        // 2. 404 Not Found
        $notFoundEx = new NotFoundHttpException('Halaman tidak ditemukan');
        $this->assertFalse($notifier->notify($notFoundEx));

        // 3. 403 Access Denied
        $deniedEx = new AccessDeniedHttpException('Akses ditolak');
        $this->assertFalse($notifier->notify($deniedEx));

        Queue::assertNothingPushed();
    }

    public function test_telegram_alert_message_does_not_contain_pii(): void
    {
        Queue::fake([SendTelegramAlertJob::class]);

        Config::set('monitoring.telegram.token', 'test-bot-token');
        Config::set('monitoring.telegram.chat_id', '99887766');
        Config::set('monitoring.enabled', true);

        $user = User::factory()->create([
            'name'     => 'Budi Rahasia Investor',
            'email'    => 'budi.rahasia@example.com',
            'role'     => 'investor',
            'phone'    => '081234567890',
            'username' => 'budirahasia',
        ]);

        $this->actingAs($user);

        $notifier = app(TelegramErrorNotifier::class);
        $notifier->notify(new Exception('Error during operation'));

        Queue::assertPushed(SendTelegramAlertJob::class, function (SendTelegramAlertJob $job) use ($user) {
            // Harus memuat user ID dan peran
            $containsUserIdAndRole = str_contains($job->message, "User #{$user->id} (investor)");

            // DILARANG memuat email, nama lengkap, atau nomor telepon
            $containsPii = str_contains($job->message, 'budi.rahasia@example.com')
                || str_contains($job->message, 'Budi Rahasia Investor')
                || str_contains($job->message, '081234567890');

            return $containsUserIdAndRole && !$containsPii;
        });
    }

    public function test_empty_credentials_acts_as_safe_noop(): void
    {
        Queue::fake([SendTelegramAlertJob::class]);

        Config::set('monitoring.telegram.token', null);
        Config::set('monitoring.telegram.chat_id', null);
        Config::set('monitoring.enabled', false);

        $notifier = app(TelegramErrorNotifier::class);
        $result = $notifier->notify(new RuntimeException('Fatal without credentials'));

        $this->assertFalse($result);
        Queue::assertNothingPushed();
    }

    public function test_sentry_scrubber_removes_sensitive_data_and_request_body(): void
    {
        $event = Event::createEvent();

        // 1. Simulasikan request dengan body, cookies, dan header sensitif
        $event->setRequest([
            'url'    => 'https://yzd-center.com/api/copytrade',
            'method' => 'POST',
            'data'   => [
                'password'        => 'SecretPass123!',
                'master_password' => 'SecretMaster456!',
                'ktp'             => '3171012345678901',
                'vps_pin'         => '1234',
            ],
            'cookies' => ['session_id' => 'abc123xyz'],
            'headers' => [
                'authorization' => 'Bearer secret-jwt-token',
                'cookie'        => 'remember_token=xyz',
                'content-type'  => 'application/json',
            ],
            'query_string' => 'token=secret-token&page=1',
        ]);

        // 2. Simulasikan user context
        $user = new UserDataBag('42');
        $user->setEmail('investor@example.com');
        $user->setUsername('investor_vip');
        $user->setIpAddress('192.168.1.1');
        $user->setMetadata('role', 'investor');
        $event->setUser($user);

        // 3. Simulasikan extra metadata
        $event->setExtra([
            'mt4_password' => 'HiddenPass!',
            'safe_param'   => 'ok',
        ]);

        // Jalankan Scrubber
        $scrubbed = SentryScrubber::scrub($event);
        $this->assertInstanceOf(Event::class, $scrubbed);

        $request = $scrubbed->getRequest();

        // Request data (body) & cookies harus terhapus bersih
        $this->assertArrayNotHasKey('data', $request);
        $this->assertArrayNotHasKey('cookies', $request);

        // Header otorisasi harus terhapus
        $this->assertArrayNotHasKey('authorization', $request['headers']);
        $this->assertArrayNotHasKey('cookie', $request['headers']);
        $this->assertSame('application/json', $request['headers']['content-type']);

        // Query string token harus di-scrub
        $this->assertStringContainsString('token=%5BSCRUBBED%5D', $request['query_string']);
        $this->assertStringContainsString('page=1', $request['query_string']);

        // User PII harus dikosongkan
        $scrubbedUser = $scrubbed->getUser();
        $this->assertNotNull($scrubbedUser);
        $this->assertSame('42', $scrubbedUser->getId());
        $this->assertNull($scrubbedUser->getEmail());
        $this->assertNull($scrubbedUser->getUsername());
        $this->assertNull($scrubbedUser->getIpAddress());
        $this->assertSame('investor', $scrubbedUser->getMetadata()['role']);

        // Extra context sensitif harus di-scrub
        $extra = $scrubbed->getExtra();
        $this->assertSame('[SCRUBBED]', $extra['mt4_password']);
        $this->assertSame('ok', $extra['safe_param']);
    }

    public function test_send_telegram_alert_job_fails_silently_on_http_error(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok'          => false,
                'description' => 'Unauthorized / Invalid bot token',
            ], 401),
        ]);

        Config::set('monitoring.telegram.token', 'invalid-token');
        Config::set('monitoring.telegram.chat_id', '123456');

        $job = new SendTelegramAlertJob('Test failure');

        // Eksekusi handle tidak boleh melempar unhandled exception (silent fail)
        $job->handle();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org');
        });
    }

    public function test_monitoring_test_command_runs_successfully(): void
    {
        // 1. Saat kredensial kosong (mode NO-OP)
        Config::set('monitoring.telegram.token', null);
        Config::set('monitoring.telegram.chat_id', null);
        Config::set('sentry.dsn', null);

        $this->artisan('monitoring:test')
            ->assertSuccessful();

        // 2. Saat kredensial Telegram terisi
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        Config::set('monitoring.telegram.token', 'test-token-12345678');
        Config::set('monitoring.telegram.chat_id', '123456789');

        $this->artisan('monitoring:test --telegram')
            ->assertSuccessful();
    }
}
