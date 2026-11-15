<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

class AgreementTemplateService
{
    private const DOTTED_LINE = '[ ..................................................... ]';

    private const DOTTED_DATE = '[ DD / MM / YYYY ]';

    private const DOTTED_BANK = '[ Nama Bank - Nomor Rekening ]';

    /**
     * Map tier slug to template file path.
     */
    public function getTemplatePath(string $tier): string
    {
        $map = [
            'tier_1' => storage_path('app/templates/perjanjian/tier_1_hfm.docx'),
            'tier_2' => storage_path('app/templates/perjanjian/tier_2_hfm.docx'),
            'tier_3' => storage_path('app/templates/perjanjian/tier_3_xm.docx'),
        ];

        return $map[$tier] ?? '';
    }

    /**
     * Generate populated agreement docx for the given tier.
     *
     * @return array{path: string, filename: string}
     */
    public function generate(string $tier, array $data = []): array
    {
        $templatePath = $this->getTemplatePath($tier);

        if (!$templatePath || !file_exists($templatePath)) {
            abort(404, "Template perjanjian untuk {$tier} tidak ditemukan.");
        }

        Settings::setOutputEscapingEnabled(true);
        $template = new TemplateProcessor($templatePath);
        $placeholders = $this->buildPlaceholders($tier, $data);

        foreach ($placeholders as $key => $value) {
            $template->setValue($key, (string) $value);
        }

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $cleanName = !empty($data['name'])
            ? Str::slug($data['name'], '_')
            : 'Investor';
        $tierName = Str::headline($tier);
        $filename = "Surat_Perjanjian_{$tierName}_{$cleanName}.docx";
        $outputPath = $tempDir . '/' . uniqid('perjanjian_', true) . '.docx';

        $template->saveAs($outputPath);

        return [
            'path'     => $outputPath,
            'filename' => $filename,
        ];
    }

    /**
     * Build tag mapping based on input data and tier.
     */
    private function buildPlaceholders(string $tier, array $data): array
    {
        $namaLengkap = !empty($data['name']) ? trim($data['name']) : self::DOTTED_LINE;
        $nik = !empty($data['nik']) ? trim($data['nik']) : self::DOTTED_LINE;

        // TTL
        $tempatLahir = !empty($data['tempat_lahir']) ? trim($data['tempat_lahir']) : '';
        $tanggalLahir = '';
        if (!empty($data['tanggal_lahir'])) {
            try {
                $tanggalLahir = Carbon::parse($data['tanggal_lahir'])->locale('id')->translatedFormat('d F Y');
            } catch (\Throwable) {
                $tanggalLahir = $data['tanggal_lahir'];
            }
        }

        if ($tempatLahir && $tanggalLahir) {
            $ttl = "{$tempatLahir}, {$tanggalLahir}";
        } elseif ($tempatLahir) {
            $ttl = $tempatLahir;
        } elseif ($tanggalLahir) {
            $ttl = $tanggalLahir;
        } else {
            $ttl = self::DOTTED_LINE;
        }

        $alamat = !empty($data['alamat']) ? trim($data['alamat']) : self::DOTTED_LINE;
        $phone = !empty($data['phone']) ? trim($data['phone']) : self::DOTTED_LINE;
        $email = !empty($data['email']) ? trim($data['email']) : self::DOTTED_LINE;
        $pekerjaan = !empty($data['pekerjaan']) ? trim($data['pekerjaan']) : self::DOTTED_LINE;

        // Tanggal Mulai
        $tanggalMulai = self::DOTTED_DATE;
        if (!empty($data['tanggal_mulai_trading'])) {
            try {
                $tanggalMulai = Carbon::parse($data['tanggal_mulai_trading'])->locale('id')->translatedFormat('d F Y');
            } catch (\Throwable) {
                $tanggalMulai = $data['tanggal_mulai_trading'];
            }
        }

        // Tanggal Selesai (Tier 3: 5 tahun dari tanggal mulai)
        $tanggalSelesai = '31 Desember 2031';
        if ($tier === 'tier_3') {
            if (!empty($data['tanggal_mulai_trading'])) {
                try {
                    $tanggalSelesai = Carbon::parse($data['tanggal_mulai_trading'])->addYears(5)->locale('id')->translatedFormat('d F Y');
                } catch (\Throwable) {
                    $tanggalSelesai = self::DOTTED_DATE;
                }
            } else {
                $tanggalSelesai = self::DOTTED_DATE;
            }
        }

        // Data Akun Trading
        $nomorAkun = !empty($data['nomor_akun_trading']) ? trim($data['nomor_akun_trading']) : self::DOTTED_LINE;

        // Estimasi Modal
        $estimasiModal = self::DOTTED_LINE;
        if (!empty($data['estimasi_modal'])) {
            $modalNum = (float) $data['estimasi_modal'];
            $estimasiModal = '$' . number_format($modalNum, 2, '.', ',');
        }

        // Rekening Pencairan
        $namaBank = !empty($data['nama_bank']) ? trim($data['nama_bank']) : '';
        $nomorRek = !empty($data['nomor_rekening']) ? trim($data['nomor_rekening']) : '';
        $atasNama = !empty($data['atas_nama_rekening']) ? trim($data['atas_nama_rekening']) : '';

        if ($namaBank && $nomorRek) {
            $rekeningPencairan = "{$namaBank} - {$nomorRek}" . ($atasNama ? " a.n. {$atasNama}" : '');
        } else {
            $rekeningPencairan = self::DOTTED_BANK;
        }

        // Ahli Waris
        $ahliWarisNama = !empty($data['ahli_waris_nama']) ? trim($data['ahli_waris_nama']) : self::DOTTED_LINE;
        $ahliWarisHub = !empty($data['ahli_waris_hubungan']) ? trim($data['ahli_waris_hubungan']) : self::DOTTED_LINE;
        $ahliWarisPhone = !empty($data['ahli_waris_phone']) ? trim($data['ahli_waris_phone']) : self::DOTTED_LINE;
        $ahliWarisAlamat = !empty($data['ahli_waris_alamat']) ? trim($data['ahli_waris_alamat']) : self::DOTTED_LINE;

        // Tanda Tangan & Lokasi
        $tanggalSurat = Carbon::now()->locale('id')->translatedFormat('d F Y');
        $namaTtd = !empty($data['name']) ? trim($data['name']) : '[ Nama Lengkap Investor ]';

        $placeholders = [
            'NAMA_LENGKAP'         => $namaLengkap,
            'NIK'                  => $nik,
            'TEMPAT_TANGGAL_LAHIR' => $ttl,
            'ALAMAT_LENGKAP'       => $alamat,
            'NOMOR_HP'             => $phone,
            'EMAIL'                => $email,
            'PEKERJAAN'            => $pekerjaan,
            'TANGGAL_MULAI'        => $tanggalMulai,
            'TANGGAL_SELESAI'      => $tanggalSelesai,
            'NOMOR_AKUN_TRADING'   => $nomorAkun,
            'ESTIMASI_MODAL'       => $estimasiModal,
            'REKENING_PENCAIRAN'   => $rekeningPencairan,
            'AHLI_WARIS_NAMA'      => $ahliWarisNama,
            'AHLI_WARIS_HUBUNGAN'  => $ahliWarisHub,
            'AHLI_WARIS_PHONE'     => $ahliWarisPhone,
            'AHLI_WARIS_ALAMAT'    => $ahliWarisAlamat,
            'TANGGAL_SURAT'        => $tanggalSurat,
            'NAMA_INVESTOR_TTD'    => $namaTtd,
        ];

        // Khusus Tier 3
        if ($tier === 'tier_3') {
            $placeholders['SERVER_BROKER'] = !empty($data['server_broker']) ? trim($data['server_broker']) : self::DOTTED_LINE;
            $placeholders['MASTER_PASSWORD'] = !empty($data['master_password']) ? trim($data['master_password']) : self::DOTTED_LINE;
            $placeholders['INVESTOR_PASSWORD'] = !empty($data['investor_password']) ? trim($data['investor_password']) : self::DOTTED_LINE;
        }

        return $placeholders;
    }
}
