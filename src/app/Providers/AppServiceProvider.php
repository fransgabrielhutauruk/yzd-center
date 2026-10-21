<?php

namespace App\Providers;

use App\Jobs\SendTelegramAlertJob;
use App\Services\Monitoring\TelegramErrorNotifier;
use Carbon\Carbon;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale', 'id'));
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));

        // Pemantauan kegagalan antrean job (Queue Failing Alert)
        Queue::failing(function (JobFailed $event): void {
            // Hindari looping bila job pengirim alert itu sendiri yang gagal
            if ($event->job->resolveName() === SendTelegramAlertJob::class) {
                return;
            }

            app(TelegramErrorNotifier::class)->notifyJobFailure(
                $event->job->resolveName(),
                $event->exception,
            );
        });
    }
}
