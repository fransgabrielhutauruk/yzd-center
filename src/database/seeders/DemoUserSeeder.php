<?php

namespace Database\Seeders;

use App\Models\RelasiReferral;
use App\Models\User;
use App\Models\UserAhliWaris;
use App\Models\UserDokumen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Affiliator Demo (username: affiliator, password: affiliator)
        $affiliator = User::updateOrCreate(
            ['username' => 'affiliator'],
            [
                'name'              => 'Affiliator Demo',
                'email'             => 'affiliator@example.com',
                'password'          => Hash::make('affiliator'),
                'role'              => 'affiliator',
                'status'            => 'active',
                'is_active'         => true,
                'email_verified_at' => now(),
                'terms_agreed_at'   => now(),
                'referral_code'     => 'AFF-DEMO',
                'nik'               => '3201010101900001',
                'phone'             => '081298765432',
                'alamat'            => 'Jl. Jenderal Gatot Subroto No. 45, Jakarta Selatan',
                'tempat_lahir'      => 'Jakarta',
                'tanggal_lahir'     => '1990-05-15',
                'pekerjaan'         => 'Trader & Business Consultant',
            ]
        );

        UserAhliWaris::updateOrCreate(
            ['user_id' => $affiliator->id],
            [
                'nama'              => 'Siti Rahma',
                'hubungan_keluarga' => 'Istri',
                'no_hp'             => '081298765433',
                'alamat'            => 'Jl. Jenderal Gatot Subroto No. 45, Jakarta Selatan',
            ]
        );

        UserDokumen::updateOrCreate(
            [
                'user_id'      => $affiliator->id,
                'tipe_dokumen' => 'ktp',
            ],
            [
                'file_name'     => 'ktp_affiliator.jpg',
                'file_path'     => 'dokumen/ktp_affiliator.jpg',
                'mime_type'     => 'image/jpeg',
                'file_size'     => 102400,
                'status'        => 'approved',
                'catatan_admin' => 'Dokumen KTP diverifikasi sistem.',
                'verified_at'   => now(),
            ]
        );

        // 2. Akun Investor Demo (username: investor, password: investor)
        $investor = User::updateOrCreate(
            ['username' => 'investor'],
            [
                'name'              => 'Investor Demo',
                'email'             => 'investor@example.com',
                'password'          => Hash::make('investor'),
                'role'              => 'investor',
                'status'            => 'active',
                'is_active'         => true,
                'email_verified_at' => now(),
                'terms_agreed_at'   => now(),
                'referral_code'     => 'INV-DEMO',
                'nik'               => '3201010101900002',
                'phone'             => '081234567890',
                'alamat'            => 'Jl. Jenderal Sudirman No. 88, Jakarta Pusat',
                'tempat_lahir'      => 'Bandung',
                'tanggal_lahir'     => '1992-08-20',
                'pekerjaan'         => 'Wiraswasta',
            ]
        );

        UserAhliWaris::updateOrCreate(
            ['user_id' => $investor->id],
            [
                'nama'              => 'Budi Santoso',
                'hubungan_keluarga' => 'Saudara Kandung',
                'no_hp'             => '081234567891',
                'alamat'            => 'Jl. Jenderal Sudirman No. 88, Jakarta Pusat',
            ]
        );

        UserDokumen::updateOrCreate(
            [
                'user_id'      => $investor->id,
                'tipe_dokumen' => 'ktp',
            ],
            [
                'file_name'     => 'ktp_investor.jpg',
                'file_path'     => 'dokumen/ktp_investor.jpg',
                'mime_type'     => 'image/jpeg',
                'file_size'     => 102400,
                'status'        => 'approved',
                'catatan_admin' => 'Dokumen KTP diverifikasi sistem.',
                'verified_at'   => now(),
            ]
        );

        // 3. Relasi Referral: Investor terdaftar di bawah jaringan Affiliator Demo
        RelasiReferral::updateOrCreate(
            [
                'affiliator_id' => $affiliator->id,
                'downline_id'   => $investor->id,
            ],
            [
                'sumber'    => 'link_otomatis',
                'joined_at' => now(),
                'status'    => 'active',
            ]
        );
    }
}
