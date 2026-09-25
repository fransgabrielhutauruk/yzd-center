<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanMaster extends Model
{
    protected $table = 'pengaturan_master';

    protected $fillable = [
        'link_referral_broker',
        'link_master_akun',
        'tier_1_min',
        'tier_2_min',
        'tier_3_min',
        'tier_1_desc',
        'tier_2_desc',
        'tier_3_desc',
        'tier_1_pdf_path',
        'tier_2_pdf_path',
        'tier_3_pdf_path',
        'kurs_usd_idr',
        'kurs_usd_idr_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'tier_1_min'              => 'decimal:2',
            'tier_2_min'              => 'decimal:2',
            'tier_3_min'              => 'decimal:2',
            'kurs_usd_idr'            => 'decimal:2',
            'kurs_usd_idr_updated_at' => 'datetime',
        ];
    }

    protected $appends = [
        'tier_1_pdf_url',
        'tier_2_pdf_url',
        'tier_3_pdf_url',
    ];

    public function getTier1PdfUrlAttribute(): ?string
    {
        return $this->tier_1_pdf_path ? route('investor.copytrade.guide_pdf', 'tier_1') : null;
    }

    public function getTier2PdfUrlAttribute(): ?string
    {
        return $this->tier_2_pdf_path ? route('investor.copytrade.guide_pdf', 'tier_2') : null;
    }

    public function getTier3PdfUrlAttribute(): ?string
    {
        return $this->tier_3_pdf_path ? route('investor.copytrade.guide_pdf', 'tier_3') : null;
    }

    /**
     * Dapatkan konfigurasi master aktif (singleton record).
     */
    public static function getSettings(): self
    {
        $settings = static::first();

        if (!$settings) {
            $settings = static::create([
                'link_referral_broker'    => 'https://hfm.com/?ref=YZD',
                'link_master_akun'        => 'https://mql5.com/yzd-master',
                'tier_1_min'              => 100,
                'tier_2_min'              => 5000,
                'tier_3_min'              => 300000,
                'tier_1_desc'             => "1. Buka akun tipe Cent di broker HFM melalui link referral resmi.\n2. Leverage yang disarankan 1:500 s/d 1:1000.\n3. Lakukan deposit riil sesuai batas minimal.\n4. Masukkan nomor akun MT4 pada sistem YZD Center.",
                'tier_2_desc'             => "1. Buka akun tipe Standard di broker XM / HFM melalui link referral resmi.\n2. Eksekusi order langsung ke liquidity provider.\n3. Lakukan deposit riil modal menengah.\n4. Lengkapi nomor akun MT4 dan data penarikan bank.",
                'tier_3_desc'             => "1. Buka akun MT4 Dedicated VPS dengan batas modal besar.\n2. Penyiapan dedicated VPS berkecepatan tinggi oleh tim teknis YZD Center.\n3. Wajib melengkapi Master Password & Investor Password MT4 (dienkripsi aman).\n4. Dapatkan akses pemantauan privat dan sinyal eksklusif.",
                'kurs_usd_idr'            => 16796.00,
                'kurs_usd_idr_updated_at' => now(),
            ]);
        }

        return $settings;
    }
}
