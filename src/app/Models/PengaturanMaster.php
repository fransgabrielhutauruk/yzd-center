<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanMaster extends Model
{
    public const TIER_1_MIN_USD = 0.0;

    public const TIER_2_MIN_USD = 5000.0;

    public const TIER_3_MIN_USD = 300000.0;

    protected $table = 'pengaturan_master';

    protected $fillable = [
        'kurs_usd_idr',
        'kurs_usd_idr_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'kurs_usd_idr'            => 'decimal:2',
            'kurs_usd_idr_updated_at' => 'datetime',
        ];
    }

    /**
     * Dapatkan konfigurasi master aktif (singleton record untuk cadangan kurs USD-IDR).
     */
    public static function getSettings(): self
    {
        $settings = static::first();

        if (!$settings) {
            $settings = static::create([
                'kurs_usd_idr'            => 16796.00,
                'kurs_usd_idr_updated_at' => now(),
            ]);
        }

        return $settings;
    }
}
