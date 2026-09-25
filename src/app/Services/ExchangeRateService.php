<?php

namespace App\Services;

use App\Models\PengaturanMaster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public const API_URL = 'https://api.frankfurter.dev/v1/latest?base=USD&symbols=IDR';

    public const CACHE_KEY = 'yzd_kurs_usd_idr_data';

    public const DEFAULT_FALLBACK_RATE = 16796.0;

    /**
     * Mengambil nilai kurs USD ke IDR (dari Cache, atau fetch API jika cache kosong).
     */
    public function getUsdToIdrRate(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && isset($cached['rate']) && $cached['rate'] > 0) {
            return $cached;
        }

        return $this->fetchAndStoreRate();
    }

    /**
     * Mengambil data harian nilai kurs dari API Frankfurter publik dan menyimpannya ke Cache & Database.
     * Jika terjadi kegagalan koneksi, sistem secara otomatis beralih ke nilai cadangan (fallback).
     */
    public function fetchAndStoreRate(): array
    {
        try {
            $response = Http::timeout(5)->get(self::API_URL);

            if ($response->successful()) {
                $data = $response->json();
                $rate = (float) ($data['rates']['IDR'] ?? 0);

                if ($rate > 0) {
                    $now = now();
                    $payload = [
                        'rate'       => $rate,
                        'updated_at' => $now->toIso8601String(),
                        'date'       => $data['date'] ?? $now->toDateString(),
                        'is_live'    => true,
                        'source'     => 'api',
                    ];

                    // 1. Simpan ke database (pengaturan_master) sebagai cadangan permanen
                    $settings = PengaturanMaster::getSettings();
                    $settings->update([
                        'kurs_usd_idr'            => $rate,
                        'kurs_usd_idr_updated_at' => $now,
                    ]);

                    // 2. Simpan ke Cache hingga 26 jam (melewati jadwal fetch 06:00 WIB berikutnya)
                    Cache::put(self::CACHE_KEY, $payload, now()->addHours(26));

                    return $payload;
                }
            }

            Log::warning('Respon API Frankfurter tidak valid untuk kurs USD ke IDR: ' . $response->body());
        } catch (\Throwable $e) {
            Log::warning('Gagal mengambil kurs USD ke IDR dari Frankfurter API: ' . $e->getMessage());
        }

        // Ketahanan Sistem (Fallback): Gunakan nilai terakhir yang tersimpan di DB atau default
        return $this->getFallbackRate();
    }

    /**
     * Membaca kurs cadangan saat API tidak dapat dihubungi.
     */
    protected function getFallbackRate(): array
    {
        $settings = PengaturanMaster::getSettings();
        $savedRate = (float) ($settings->kurs_usd_idr ?? 0);

        if ($savedRate > 0) {
            $fallback = [
                'rate'       => $savedRate,
                'updated_at' => $settings->kurs_usd_idr_updated_at?->toIso8601String(),
                'date'       => $settings->kurs_usd_idr_updated_at?->toDateString(),
                'is_live'    => false,
                'source'     => 'database_fallback',
            ];
        } else {
            $fallback = [
                'rate'       => self::DEFAULT_FALLBACK_RATE,
                'updated_at' => null,
                'date'       => null,
                'is_live'    => false,
                'source'     => 'default_fallback',
            ];
        }

        // Simpan fallback ke cache sementara (1 jam) untuk ketahanan performa jika API offline
        Cache::put(self::CACHE_KEY, $fallback, now()->addHour());

        return $fallback;
    }
}
