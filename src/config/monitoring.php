<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Error Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk mengirimkan notifikasi instan ke chat Telegram developer
    | saat terjadi exception 5xx tak tertangani, kegagalan antrean job,
    | atau scheduler error di lingkungan aplikasi.
    |
    */
    'telegram' => [
        'token'   => env('MONITORING_TELEGRAM_TOKEN'),
        'chat_id' => env('MONITORING_TELEGRAM_CHAT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sentry Organization Slug (Untuk Deep-Link Telegram ke Sentry)
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk menyertakan tautan langsung ke insiden Sentry di dalam
    | pesan notifikasi Telegram (contoh: https://{org_slug}.sentry.io/issues/).
    |
    */
    'sentry_org_slug' => env('SENTRY_ORGANIZATION_SLUG'),

    /*
    |--------------------------------------------------------------------------
    | Throttle Window (Menit)
    |--------------------------------------------------------------------------
    |
    | Menentukan berapa lama jenis error serupa ditahan agar tidak membanjiri
    | chat Telegram (1 pesan per jenis error / fingerprint / X menit).
    |
    */
    'throttle_minutes' => (int) env('MONITORING_THROTTLE_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Status Aktif Monitoring Telegram
    |--------------------------------------------------------------------------
    |
    | Aktif secara otomatis bila token dan chat_id terisi serta bukan di
    | lingkungan pengujian otomatis (testing).
    |
    */
    'enabled' => (bool) (
        env('MONITORING_TELEGRAM_TOKEN') &&
        env('MONITORING_TELEGRAM_CHAT_ID') &&
        env('APP_ENV') !== 'testing'
    ),
];
