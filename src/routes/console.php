<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:update-usd-exchange-rate')
    ->dailyAt('06:00')
    ->timezone('Asia/Jakarta')
    ->onFailure(function ($e = null) {
        $exception = $e instanceof Throwable ? $e : new RuntimeException('Scheduled task app:update-usd-exchange-rate gagal');
        app(\App\Services\Monitoring\TelegramErrorNotifier::class)->notifyScheduleFailure('app:update-usd-exchange-rate', $exception);
    });

Schedule::command('app:send-balance-recap-reminder')
    ->monthlyOn(1, '07:00')
    ->timezone('Asia/Jakarta')
    ->onFailure(function ($e = null) {
        $exception = $e instanceof Throwable ? $e : new RuntimeException('Scheduled task app:send-balance-recap-reminder gagal');
        app(\App\Services\Monitoring\TelegramErrorNotifier::class)->notifyScheduleFailure('app:send-balance-recap-reminder', $exception);
    });
