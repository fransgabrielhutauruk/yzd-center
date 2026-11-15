<?php

namespace Database\Seeders;

use App\Models\EdukasiFaq;
use App\Models\EdukasiFaqTopik;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder topik dan pertanyaan FAQ untuk modul Edukasi.
 */
class EdukasiFaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $adminId = $admin?->id;

        // ---------------------------------------------------------------------
        // 4. Data Topik Tanya Jawab (FAQ) & Pertanyaan/Jawaban
        // ---------------------------------------------------------------------
        $faqTopikData = [
            [
                'nama'      => 'Umum & Legalitas',
                'deskripsi' => 'Informasi umum mengenai model bisnis, legalitas, kepemilikan dana, dan kontrak perjanjian.',
                'urutan'    => 1,
                'faqs'      => [
                    [
                        'pertanyaan' => 'Apakah dana modal saya aman di akun broker pribadi?',
                        'jawaban'    => "Sistem copytrade YZD Center bekerja dengan prinsip non-custodial. Master Trader hanya memiliki hak mengeksekusi order beli dan jual secara otomatis pada akun trading Anda. Master Trader SAMA SEKALI TIDAK MEMILIKI AKSES ataupun wewenang untuk melakukan penarikan dana (withdrawal). Penarikan dana modal maupun keuntungan hanya dapat diproses langsung oleh Anda sendiri menuju rekening bank pribadi terdaftar.",
                        'urutan'     => 1,
                    ],
                    [
                        'pertanyaan' => 'Mengapa diperlukan surat perjanjian bermaterai?',
                        'jawaban'    => "Surat perjanjian bermaterai memberikan kepastian hukum yang mengikat secara sah antara Anda sebagai Investor dan Master Trader. Dokumen ini menegaskan hak bagi hasil profit, pembagian komisi, serta batasan wewenang teknis trading.",
                        'urutan'     => 2,
                    ],
                ],
            ],
            [
                'nama'      => 'Sistem Copytrade & MT4',
                'deskripsi' => 'Seputar teknis penyalinan transaksi, platform MetaTrader 4, dan server VPS.',
                'urutan'    => 2,
                'faqs'      => [
                    [
                        'pertanyaan' => 'Apakah saya harus menyalakan komputer/laptop selama 24 jam?',
                        'jawaban'    => "Tidak perlu. Seluruh proses eksekusi sinyal dan penyalinan transaksi dikelola secara terpusat melalui infrastruktur Virtual Private Server (VPS) berkecepatan tinggi dengan uptime 99.9%. Anda cukup memeriksa riwayat trading melalui aplikasi MT4 ponsel pintar Anda.",
                        'urutan'     => 1,
                    ],
                    [
                        'pertanyaan' => 'Berapa ketahanan modal (pip buffer) yang diterapkan?',
                        'jawaban'    => "YZD Center mengutamakan manajemen risiko modal yang teruji. Rumus lot yang digunakan dirancang untuk menahan pergerakan pasar minimal 50.000 hingga 75.000 pip, sehingga potensi Margin Call dapat dihindari secara disiplin.",
                        'urutan'     => 2,
                    ],
                ],
            ],
            [
                'nama'      => 'Deposit, Pembagian Hasil & Penarikan',
                'deskripsi' => 'Tata cara penyetoran dana, skema profit sharing 30:30:40, dan penarikan keuntungan.',
                'urutan'    => 3,
                'faqs'      => [
                    [
                        'pertanyaan' => 'Bagaimana skema pembagian keuntungan (profit share)?',
                        'jawaban'    => "Dari total keuntungan yang diraih pada periode evaluasi, pembagian dialokasikan sebagai berikut: 30% Master Fee (jasa trading), 30% Investor Payout (penarikan laba oleh investor), dan 40% dialokasikan untuk compounding modal agar pertumbuhan dana semakin optimal.",
                        'urutan'     => 1,
                    ],
                    [
                        'pertanyaan' => 'Kapan saya dapat menarik keuntungan atau modal awal?',
                        'jawaban'    => "Modal awal dan profit berada 100% di akun broker Anda. Anda memiliki hak penuh untuk menarik dana Anda sewaktu-waktu sesuai ketentuan penarikan broker mitra.",
                        'urutan'     => 2,
                    ],
                ],
            ],
            [
                'nama'      => 'Kemitraan & Afiliasi',
                'deskripsi' => 'Program referral, skema 4-Tier komisi, dan evaluasi 90 hari.',
                'urutan'    => 4,
                'faqs'      => [
                    [
                        'pertanyaan' => 'Kapan komisi affiliator dapat diklaim?',
                        'jawaban'    => "Komisi affiliator dapat diajukan setelah akun copytrade downline binaan Anda berstatus aktif menyalin selama minimal 90 hari kalender, dan akun berada dalam kondisi laba bersih (positif profit).",
                        'urutan'     => 1,
                    ],
                    [
                        'pertanyaan' => 'Berapa besaran persentase komisi affiliator?',
                        'jawaban'    => "Besaran komisi dihitung dari bagian Master Fee berdasarkan tingkatan modal downline: Tier 1 (50%), Tier 2 (40%), Tier 3 (30%), dan Tier 4 (20%).",
                        'urutan'     => 2,
                    ],
                ],
            ],
        ];

        foreach ($faqTopikData as $topikItem) {
            $topik = EdukasiFaqTopik::updateOrCreate(
                ['nama' => $topikItem['nama']],
                [
                    'deskripsi' => $topikItem['deskripsi'],
                    'urutan'    => $topikItem['urutan'],
                    'is_active' => true,
                ]
            );

            foreach ($topikItem['faqs'] as $faqItem) {
                EdukasiFaq::updateOrCreate(
                    [
                        'topik_id'   => $topik->id,
                        'pertanyaan' => $faqItem['pertanyaan'],
                    ],
                    [
                        'jawaban'    => $faqItem['jawaban'],
                        'urutan'     => $faqItem['urutan'],
                        'is_active'  => true,
                        'created_by' => $adminId,
                    ]
                );
            }
        }
    }
}
