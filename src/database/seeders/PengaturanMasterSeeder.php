<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PengaturanMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::table('pengaturan_master')->updateOrInsert(
            ['id' => 1],
            [
                'link_referral_broker'    => 'https://hfm.com/?ref=YZD',
                'link_master_akun'        => 'https://mql5.com/yzd-master',
                'tier_1_min'              => 0,
                'tier_2_min'              => 5000,
                'tier_3_min'              => 300000,
                'tier_1_desc'             => "1. Buka akun tipe Cent di broker HFM melalui link referral resmi.\n2. Leverage yang disarankan 1:500 s/d 1:1000.\n3. Alokasi modal di bawah $5.000 (bebas nominal tanpa batas minimal $100).\n4. Masukkan nomor akun MT4 pada sistem YZD Center.",
                'tier_2_desc'             => "1. Buka akun tipe Standard di broker XM / HFM melalui link referral resmi.\n2. Alokasi modal $5.000 s/d $300.000.\n3. Eksekusi order langsung ke liquidity provider.\n4. Lengkapi nomor akun MT4 dan data penarikan bank.",
                'tier_3_desc'             => "1. Buka akun MT4 Dedicated VPS dengan batas modal minimal $300.000.\n2. Penyiapan dedicated VPS berkecepatan tinggi oleh tim teknis YZD Center.\n3. Wajib melengkapi Master Password & Investor Password MT4 (dienkripsi aman).\n4. Dapatkan akses pemantauan privat dan sinyal eksklusif.",
                'kurs_usd_idr'            => 16796.00,
                'kurs_usd_idr_updated_at' => now(),
                'updated_at'              => now(),
            ]
        );
    }
}
