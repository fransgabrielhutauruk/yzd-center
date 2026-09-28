<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\User;
use App\Models\UserDokumen;
use App\Notifications\CopytradeAccountStatusChangedNotification;
use App\Notifications\NewCopytradeSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class CopytradeService
{
    /**
     * Tambah akun copytrade baru untuk investor.
     *
     * @param array $data Data form termasuk modal riil, tier, nomor akun, dsb.
     * @param UploadedFile|null $suratPerjanjian Berkas surat perjanjian (wajib pada pendaftaran awal)
     */
    public function storeCopytrade(User $user, array $data, ?UploadedFile $suratPerjanjian): AkunCopytrade
    {
        return DB::transaction(function () use ($user, $data, $suratPerjanjian): AkunCopytrade {
            $tier = $data['tier_investasi'] ?? $data['tier'] ?? 'tier_1';

            $broker = $data['broker'] ?? match ($tier) {
                'tier_2', 'tier_3' => 'XM',
                default            => 'HFM',
            };

            $tipeAkun = $data['tipe_akun'] ?? match ($tier) {
                'tier_1' => 'cent',
                'tier_3' => 'private_vps',
                default  => 'standard',
            };

            $akunData = [
                'user_id'            => $user->id,
                'tier'               => $tier,
                'broker'             => $broker,
                'tipe_akun'          => $tipeAkun,
                'nomor_akun_trading' => $data['nomor_akun_trading'],
                'modal_riil'         => $data['modal_riil'],
                'tanggal_mulai'      => $data['tanggal_mulai_trading'] ?? $data['tanggal_mulai'] ?? now()->toDateString(),
                'nama_bank'          => $data['nama_bank'],
                'nomor_rekening'     => $data['nomor_rekening'],
                'atas_nama_rekening' => $data['atas_nama_rekening'],
                'status'             => 'pending',
                'url_mql5'           => $data['url_mql5'] ?? null,
            ];

            // Kredensial khusus Tier 3 (Private VPS)
            if (!empty($data['server_broker'])) {
                $akunData['server_broker'] = $data['server_broker'];
            }
            if (!empty($data['master_password'])) {
                $akunData['master_password'] = $data['master_password'];
            }
            if (!empty($data['investor_password'])) {
                $akunData['investor_password'] = $data['investor_password'];
            }

            // Simpan Surat Perjanjian jika dilampirkan
            if ($suratPerjanjian) {
                $storedPath = $suratPerjanjian->store("copytrade_agreements/{$user->id}", 'local');
                $akunData['surat_perjanjian_path'] = $storedPath;

                UserDokumen::updateOrCreate(
                    [
                        'user_id'      => $user->id,
                        'tipe_dokumen' => 'perjanjian',
                    ],
                    [
                        'file_path'          => $storedPath,
                        'file_name'          => $suratPerjanjian->getClientOriginalName(),
                        'mime_type'          => $suratPerjanjian->getClientMimeType() ?? $suratPerjanjian->getMimeType(),
                        'file_size'          => $suratPerjanjian->getSize(),
                        'status'             => 'pending',
                        'catatan_admin'      => null,
                        'is_quality_warning' => false,
                        'verified_at'        => null,
                        'verified_by'        => null,
                    ]
                );
            }

            $akun = AkunCopytrade::create($akunData);

            // Kirim notifikasi database in-app ke seluruh administrator
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewCopytradeSubmittedNotification($user, $akun));
            }

            return $akun;
        });
    }

    /**
     * Verifikasi pendaftaran akun copy trade oleh Administrator.
     *
     * @throws ValidationException
     */
    public function verifyAccount(AkunCopytrade $akun, string $status, ?string $catatanAdmin, User $verifier): AkunCopytrade
    {
        if (!in_array($status, ['active', 'rejected'], true)) {
            throw new \InvalidArgumentException("Status keputusan copy trade tidak valid: {$status}");
        }

        if ($status === 'rejected' && empty(trim((string) $catatanAdmin))) {
            throw ValidationException::withMessages([
                'catatan_admin' => 'Catatan admin wajib diisi ketika menolak permohonan copy trade.',
            ]);
        }

        return DB::transaction(function () use ($akun, $status, $catatanAdmin, $verifier): AkunCopytrade {
            $akun->update([
                'status'          => $status,
                'catatan_admin'   => $status === 'active' ? ($catatanAdmin ?: null) : $catatanAdmin,
                'verified_by'     => $verifier->id,
                'verified_at'     => now(),
                'copy_started_at' => $status === 'active' ? now() : null,
            ]);

            if ($status === 'active') {
                UserDokumen::where('user_id', $akun->user_id)
                    ->where('tipe_dokumen', 'perjanjian')
                    ->update([
                        'status'      => 'approved',
                        'verified_at' => now(),
                        'verified_by' => $verifier->id,
                    ]);
            }

            // Kirim notifikasi database in-app ke investor pemilik akun
            $akun->user->notify(new CopytradeAccountStatusChangedNotification($akun, $status, $catatanAdmin));

            return $akun;
        });
    }

    /**
     * Hasilkan dokumen Word (DOCX) surat perjanjian kemitraan yang terisi otomatis
     * dengan data profil investor dan data formulir pendaftaran.
     *
     * @param User $user Pengguna investor
     * @param string $tier Tier investasi (tier_1, tier_2, tier_3)
     * @param array $formData Data dari formulir copytrade (nomor akun, modal, bank, dsb.)
     * @return string|null Path file temporer yang dihasilkan, atau null jika template tidak ditemukan
     */
    public function generatePerjanjianDocx(User $user, string $tier, array $formData = []): ?string
    {
        $fileName = match ($tier) {
            'tier_1' => 'tier_1_hfm.docx',
            'tier_2' => 'tier_2_xm.docx',
            'tier_3' => 'tier_3_xm.docx',
            default  => null,
        };

        if (!$fileName) {
            return null;
        }

        $sourcePath = storage_path("app/templates/perjanjian/{$fileName}");
        if (!file_exists($sourcePath)) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'perjanjian_') . '.docx';
        if (!copy($sourcePath, $tempFile)) {
            return null;
        }

        $zip = new \ZipArchive;
        if ($zip->open($tempFile) !== true) {
            @unlink($tempFile);

            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            @unlink($tempFile);

            return null;
        }

        // Normalisasi proofing errors dan pemisahan text run yang memecah placeholder
        $xml = preg_replace('/<w:proofErr[^>]*\/>/i', '', $xml);
        $xml = preg_replace('/(<\/w:t><\/w:r>)(<w:r[^>]*><w:t[^>]*>)/i', '', $xml);

        $user->loadMissing('ahliWaris');

        $tanggalMulaiInput = !empty($formData['tanggal_mulai_trading'])
            ? $formData['tanggal_mulai_trading']
            : now()->toDateString();

        $tanggalMulaiFormatted = function_exists('tanggal_indo')
            ? tanggal_indo($tanggalMulaiInput)
            : $tanggalMulaiInput;

        try {
            $tanggalSelesaiFormatted = function_exists('tanggal_indo')
                ? tanggal_indo(\Carbon\Carbon::parse($tanggalMulaiInput)->addYears(5))
                : \Carbon\Carbon::parse($tanggalMulaiInput)->addYears(5)->format('d F Y');
        } catch (\Throwable) {
            $tanggalSelesaiFormatted = '-';
        }

        $tanggalSuratFormatted = function_exists('tanggal_indo')
            ? tanggal_indo(now())
            : now()->format('d F Y');

        $ttl = trim(
            ($user->tempat_lahir ? $user->tempat_lahir . ', ' : '') .
            ($user->tanggal_lahir ? (function_exists('tanggal_indo') ? tanggal_indo($user->tanggal_lahir) : $user->tanggal_lahir) : '')
        );

        $rekeningParts = array_filter([
            $formData['nama_bank'] ?? null,
            $formData['nomor_rekening'] ?? null,
            !empty($formData['atas_nama_rekening']) ? 'a.n ' . $formData['atas_nama_rekening'] : null,
        ]);
        $rekening = !empty($rekeningParts) ? implode(' - ', $rekeningParts) : '-';

        $modalRiilFormatted = !empty($formData['modal_riil'])
            ? '$' . number_format((float) $formData['modal_riil'], 2, '.', ',')
            : '-';

        $replacements = [
            '${NAMA_LENGKAP}'         => htmlspecialchars($user->name, ENT_XML1),
            '${NAMA_INVESTOR_TTD}'    => htmlspecialchars($user->name, ENT_XML1),
            '${NIK}'                  => htmlspecialchars($user->nik ?? '-', ENT_XML1),
            '${TEMPAT_TANGGAL_LAHIR}' => htmlspecialchars($ttl ?: '-', ENT_XML1),
            '${ALAMAT_LENGKAP}'       => htmlspecialchars($user->alamat ?? '-', ENT_XML1),
            '${NOMOR_HP}'             => htmlspecialchars($user->phone ?? '-', ENT_XML1),
            '${EMAIL}'                => htmlspecialchars($user->email ?? '-', ENT_XML1),
            '${PEKERJAAN}'            => htmlspecialchars($user->pekerjaan ?? '-', ENT_XML1),
            '${TANGGAL_MULAI}'        => htmlspecialchars($tanggalMulaiFormatted, ENT_XML1),
            '${TANGGAL_SELESAI}'      => htmlspecialchars($tanggalSelesaiFormatted, ENT_XML1),
            '${NOMOR_AKUN_TRADING}'   => htmlspecialchars($formData['nomor_akun_trading'] ?? '-', ENT_XML1),
            '${SERVER_BROKER}'        => htmlspecialchars($formData['server_broker'] ?? '-', ENT_XML1),
            '${MASTER_PASSWORD}'      => htmlspecialchars($formData['master_password'] ?? '-', ENT_XML1),
            '${INVESTOR_PASSWORD}'    => htmlspecialchars($formData['investor_password'] ?? '-', ENT_XML1),
            '${ESTIMASI_MODAL}'       => htmlspecialchars($modalRiilFormatted, ENT_XML1),
            '${REKENING_PENCAIRAN}'   => htmlspecialchars($rekening, ENT_XML1),
            '${AHLI_WARIS_NAMA}'      => htmlspecialchars($user->ahliWaris?->nama ?? '-', ENT_XML1),
            '${AHLI_WARIS_HUBUNGAN}'  => htmlspecialchars($user->ahliWaris?->hubungan_keluarga ?? '-', ENT_XML1),
            '${AHLI_WARIS_PHONE}'     => htmlspecialchars($user->ahliWaris?->no_hp ?? '-', ENT_XML1),
            '${TANGGAL_SURAT}'        => htmlspecialchars($tanggalSuratFormatted, ENT_XML1),
        ];

        $xml = str_replace(array_keys($replacements), array_values($replacements), $xml);

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $tempFile;
    }
}
