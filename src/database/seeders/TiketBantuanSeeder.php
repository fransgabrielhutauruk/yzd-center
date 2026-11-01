<?php

namespace Database\Seeders;

use App\Models\TiketBantuan;
use App\Models\TiketBantuanBalasan;
use App\Models\User;
use Illuminate\Database\Seeder;

class TiketBantuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $members = User::where('role', 'member')->get();

        if ($members->isEmpty()) {
            return;
        }

        $member1 = $members->first();
        $member2 = $members->skip(1)->first() ?? $member1;

        // Tiket 1: Open (Butuh Respons)
        $t1 = TiketBantuan::firstOrCreate(
            ['nomor' => 'TKT-202610-0001'],
            [
                'user_id'       => $member1->id,
                'subjek'        => 'Gagal unggah foto identitas KTP (Ukuran melebihi batas)',
                'status'        => TiketBantuan::STATUS_OPEN,
                'last_reply_at' => now()->subHours(2),
                'last_reply_by' => 'member',
            ]
        );

        TiketBantuanBalasan::firstOrCreate(
            ['tiket_id' => $t1->id, 'pengirim' => 'member'],
            [
                'user_id' => $member1->id,
                'pesan'   => 'Halo Admin, saya mencoba mengunggah foto e-KTP dengan resolusi tinggi dari kamera HP saya, tetapi muncul galat koneksi terputus. Mohon bantuannya.',
            ]
        );

        // Tiket 2: Answered (Telah Dijawab Admin)
        $t2 = TiketBantuan::firstOrCreate(
            ['nomor' => 'TKT-202610-0002'],
            [
                'user_id'       => $member2->id,
                'subjek'        => 'Pertanyaan mengenai verifikasi tahap 2 copytrade broker',
                'status'        => TiketBantuan::STATUS_ANSWERED,
                'last_reply_at' => now()->subMinutes(30),
                'last_reply_by' => 'admin',
            ]
        );

        TiketBantuanBalasan::firstOrCreate(
            ['tiket_id' => $t2->id, 'user_id' => $member2->id, 'pengirim' => 'member'],
            [
                'pesan' => 'Selamat siang admin, akun MT4 saya sudah selesai deposit tahap 1, apakah saya perlu mengisi formulir lagi untuk koneksi VPS?',
            ]
        );

        if ($admin) {
            TiketBantuanBalasan::firstOrCreate(
                ['tiket_id' => $t2->id, 'user_id' => $admin->id, 'pengirim' => 'admin'],
                [
                    'pesan' => 'Halo Kak, tim teknis kami akan otomatis mengonfigurasi EA copytrade di server VPS Anda setelah deposit terverifikasi. Tidak perlu mengisi form tambahan, pantau dasbor investasi Anda secara berkala ya.',
                ]
            );
        }

        // Tiket 3: Resolved (Telah Ditandai Selesai)
        $t3 = TiketBantuan::firstOrCreate(
            ['nomor' => 'TKT-202610-0003'],
            [
                'user_id'       => $member1->id,
                'subjek'        => 'Klaim referral downline sudah masuk dengan benar',
                'status'        => TiketBantuan::STATUS_RESOLVED,
                'last_reply_at' => now()->subDays(2),
                'last_reply_by' => 'member',
                'resolved_at'   => now()->subDays(2),
            ]
        );

        TiketBantuanBalasan::firstOrCreate(
            ['tiket_id' => $t3->id, 'user_id' => $member1->id, 'pengirim' => 'member'],
            [
                'pesan' => 'Terima kasih admin, bonus klaim referral sudah berhasil dikonfirmasi dan masuk ke daftar komisi saya.',
            ]
        );
    }
}
