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

Schedule::command('app:close-resolved-tickets --days=7')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->onFailure(function ($e = null) {
        $exception = $e instanceof Throwable ? $e : new RuntimeException('Scheduled task app:close-resolved-tickets gagal');
        app(\App\Services\Monitoring\TelegramErrorNotifier::class)->notifyScheduleFailure('app:close-resolved-tickets', $exception);
    });

Schedule::command('app:send-master-fee-reminders')
    ->dailyAt('08:00')
    ->timezone('Asia/Jakarta')
    ->onFailure(function ($e = null) {
        $exception = $e instanceof Throwable ? $e : new RuntimeException('Scheduled task app:send-master-fee-reminders gagal');
        app(\App\Services\Monitoring\TelegramErrorNotifier::class)->notifyScheduleFailure('app:send-master-fee-reminders', $exception);
    });
