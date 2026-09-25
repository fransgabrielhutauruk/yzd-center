<?php

namespace Database\Seeders;

use App\Models\LaporanKeuntungan;
use App\Models\LaporanKeuntunganGambar;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class LaporanKeuntunganSeeder extends Seeder
{
    /**
     * Jalankan seeder laporan keuntungan dan materi promosi.
     */
    public function run(): void
    {
        $admin = User::where('role', 'administrator')->first();
        if (!$admin) {
            return;
        }

        $reportsData = [
            [
                'tipe'               => 'bulanan',
                'judul'              => 'Kalender Keuntungan Januari 2026 (+18.4%)',
                'tanggal'            => null,
                'bulan'              => 1,
                'tahun'              => 2026,
                'profit_persen'      => 18.40,
                'profit_nominal_usd' => 1840.50,
                'is_kalender'        => true,
                'deskripsi'          => 'Performa trading Copytrade YZD Center sepanjang bulan Januari 2026 mencatatkan hasil gemilang +18.4% dengan drawdown maksimal terkendali di bawah 4.2%. Seluruh entry didominasi pair XAUUSD dan EURUSD.',
                'status'             => 'published',
                'photos'             => [
                    ['name' => 'kalender-profit-januari-2026.png', 'caption' => 'Screenshot Kalender Myfxbook Januari 2026'],
                    ['name' => 'equity-curve-januari-2026.png', 'caption' => 'Grafik Pertumbuhan Equity Akun Master'],
                ],
            ],
            [
                'tipe'               => 'bulanan',
                'judul'              => 'Rekapitulasi Keuntungan Februari 2026 (+14.2%)',
                'tanggal'            => null,
                'bulan'              => 2,
                'tahun'              => 2026,
                'profit_persen'      => 14.20,
                'profit_nominal_usd' => 1420.00,
                'is_kalender'        => true,
                'deskripsi'          => 'Bulan Februari ditutup dengan konsistensi profit +14.2%. Sistem trading semi-algo dengan manajemen risiko ketat berhasil melewati volatilitas tinggi berita NFP dan FOMC secara mulus.',
                'status'             => 'published',
                'photos'             => [
                    ['name' => 'kalender-profit-februari-2026.png', 'caption' => 'Rekapitulasi Kalender MT4 Februari 2026'],
                ],
            ],
            [
                'tipe'               => 'harian',
                'judul'              => 'Profit Harian 24 September 2026 (+1.85%)',
                'tanggal'            => '2026-09-24',
                'bulan'              => null,
                'tahun'              => null,
                'profit_persen'      => 1.85,
                'profit_nominal_usd' => 185.00,
                'is_kalender'        => false,
                'deskripsi'          => 'Sesi London & New York menghasilkan 3 entry take profit bersih pada pair Gold (XAUUSD). Disiplin money management tetap diutamakan.',
                'status'             => 'published',
                'photos'             => [
                    ['name' => 'daily-profit-2026-09-24.png', 'caption' => 'History Trade MT4 Harian 24 Sept 2026'],
                ],
            ],
            [
                'tipe'               => 'harian',
                'judul'              => 'Profit Harian 25 September 2026 (+2.10%)',
                'tanggal'            => '2026-09-25',
                'bulan'              => null,
                'tahun'              => null,
                'profit_persen'      => 2.10,
                'profit_nominal_usd' => 210.00,
                'is_kalender'        => false,
                'deskripsi'          => 'Target profit harian tercapai maksimal sebelum penutupan sesi akhir pekan. Akun copytrade investor otomatis tersinkronisasi tanpa delay.',
                'status'             => 'published',
                'photos'             => [
                    ['name' => 'daily-profit-2026-09-25-chart.png', 'caption' => 'Setup Entry Scalping Gold M15'],
                    ['name' => 'daily-profit-2026-09-25-result.png', 'caption' => 'Rekap PnL Harian Closed Positions'],
                ],
            ],
        ];

        foreach ($reportsData as $item) {
            $photos = $item['photos'];
            unset($item['photos']);

            $report = LaporanKeuntungan::create(array_merge($item, [
                'created_by' => $admin->id,
            ]));

            // Buat sampel gambar dummy
            $order = 0;
            foreach ($photos as $photo) {
                $dirPath = "profit_reports/{$report->id}";
                $fileName = $photo['name'];
                $filePath = "{$dirPath}/{$fileName}";

                $imgContent = $this->generateDummyImage($item['judul'], $photo['caption']);
                Storage::disk('local')->put($filePath, $imgContent);

                LaporanKeuntunganGambar::create([
                    'laporan_keuntungan_id' => $report->id,
                    'file_path'             => $filePath,
                    'file_name'             => $fileName,
                    'file_size'             => strlen($imgContent),
                    'mime_type'             => 'image/png',
                    'caption'               => $photo['caption'],
                    'urutan'                => $order++,
                ]);
            }
        }
    }

    /**
     * Buat dummy banner image PNG menggunakan GD.
     */
    private function generateDummyImage(string $title, string $subtitle): string
    {
        $width = 800;
        $height = 500;
        $image = imagecreatetruecolor($width, $height);

        // Warna Latar (Slate-900 / Emerald Theme)
        $bg = imagecolorallocate($image, 15, 23, 42); // #0f172a
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        // Header Border
        $emerald = imagecolorallocate($image, 16, 185, 129); // #10b981
        imagefilledrectangle($image, 0, 0, $width, 8, $emerald);

        // Kartu Inner
        $cardBg = imagecolorallocate($image, 30, 41, 59); // #1e293b
        imagefilledrectangle($image, 40, 50, $width - 40, $height - 50, $cardBg);

        // Teks Brand
        $white = imagecolorallocate($image, 255, 255, 255);
        $gray = imagecolorallocate($image, 148, 163, 184);

        imagestring($image, 5, 60, 80, 'YZD CENTER - OFFICIAL TRADING PERFORMANCE', $emerald);
        imagestring($image, 5, 60, 130, substr($title, 0, 45), $white);
        imagestring($image, 4, 60, 170, substr($subtitle, 0, 55), $gray);
        imagestring($image, 3, 60, 240, 'Materi Promosi Resmi untuk Mitra Affiliator', $white);
        imagestring($image, 2, 60, 270, 'Diverifikasi secara berkala dari Akun Master Copytrade MT4', $gray);
        imagestring($image, 3, 60, 410, 'Website: https://yzd-center.com', $emerald);

        ob_start();
        imagepng($image);
        $content = ob_get_clean();
        imagedestroy($image);

        return $content ?: '';
    }
}
