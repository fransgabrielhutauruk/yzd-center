<?php

namespace App\Http\Controllers;

use App\Models\ProfitMasterPostImage;
use App\Models\TiketBantuanLampiran;
use App\Models\UserDokumen;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentStreamController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Tampilkan (stream) berkas privat terotentikasi.
     */
    public function show(Request $request, int $id): StreamedResponse
    {
        $dokumen = UserDokumen::with('user')->findOrFail($id);
        $user = $request->user();

        // Otorisasi: Hanya Admin atau Pemilik Dokumen yang dapat mengakses
        if (!$user || (!$user->isAdmin() && $user->id !== $dokumen->user_id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat berkas dokumen ini.');
        }

        if (!Storage::disk('local')->exists($dokumen->file_path)) {
            abort(404, 'Berkas fisik tidak ditemukan di penyimpanan server.');
        }

        if ($user->isAdmin()) {
            $actor = AuditLogService::formatActor($user);
            $targetUser = AuditLogService::formatTargetUser($dokumen->user, $dokumen->user_id);

            $this->auditLogService->record(
                kategori: 'kyc_dokumen',
                aksi: 'lihat_dokumen',
                deskripsi: "{$actor} melihat berkas {$dokumen->tipe_dokumen} milik {$targetUser}.",
                subject: $dokumen,
                tingkat: 'info',
                user: $user,
                withCooldown: true
            );
        }

        return Storage::disk('local')->response(
            $dokumen->file_path,
            $dokumen->file_name,
            [
                'Cache-Control'          => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'                 => 'no-cache',
                'Expires'                => '0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options'        => 'SAMEORIGIN',
            ]
        );
    }

    /**
     * Tampilkan (stream) berkas bukti chat klaim referral privat terotentikasi.
     */
    public function showClaimProof(Request $request, int $id): StreamedResponse
    {
        $claim = \App\Models\KlaimReferral::with(['affiliator', 'downline'])->findOrFail($id);
        $user = $request->user();

        // Otorisasi: Hanya Admin atau Affiliator pemohon yang dapat mengakses
        if (!$user || (!$user->isAdmin() && $user->id !== $claim->affiliator_id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat bukti percakapan ini.');
        }

        if (empty($claim->bukti_chat_path)) {
            abort(404, 'Berkas bukti fisik tidak ditemukan di penyimpanan server.');
        }

        $path = $claim->bukti_chat_path;
        if (!Storage::disk('local')->exists($path)) {
            // Fallback backward-compatibility jika path masih mengandung prefiks 'private/'
            $fallback = preg_replace('#^private/#', '', $path);
            if (Storage::disk('local')->exists($fallback)) {
                $path = $fallback;
            } else {
                abort(404, 'Berkas bukti fisik tidak ditemukan di penyimpanan server.');
            }
        }

        if ($user->isAdmin()) {
            $actor = AuditLogService::formatActor($user);
            $affiliatorTag = AuditLogService::formatTargetUser($claim->affiliator, $claim->affiliator_id);
            $downlineTag = $claim->downline?->username ? " untuk investor @{$claim->downline->username}" : '';

            $this->auditLogService->record(
                kategori: 'komisi_afiliasi',
                aksi: 'lihat_dokumen',
                deskripsi: "{$actor} melihat bukti percakapan klaim referral milik {$affiliatorTag}{$downlineTag}.",
                subject: $claim,
                tingkat: 'info',
                user: $user,
                withCooldown: true
            );
        }

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Cache-Control'          => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'                 => 'no-cache',
                'Expires'                => '0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options'        => 'SAMEORIGIN',
            ]
        );
    }

    /**
     * Tampilkan (stream) berkas surat perjanjian copytrade privat terotentikasi.
     */
    public function showCopytradeAgreement(Request $request, int $id): StreamedResponse
    {
        $akun = \App\Models\AkunCopytrade::with('user')->findOrFail($id);
        $user = $request->user();

        // Otorisasi: Hanya Admin atau Investor pemilik akun yang dapat mengakses
        if (!$user || (!$user->isAdmin() && $user->id !== $akun->user_id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat surat perjanjian ini.');
        }

        if (empty($akun->surat_perjanjian_path)) {
            abort(404, 'Berkas fisik surat perjanjian tidak ditemukan di penyimpanan server.');
        }

        $path = $akun->surat_perjanjian_path;
        if (!Storage::disk('local')->exists($path)) {
            $fallback = preg_replace('#^private/#', '', $path);
            if (Storage::disk('local')->exists($fallback)) {
                $path = $fallback;
            } else {
                abort(404, 'Berkas fisik surat perjanjian tidak ditemukan di penyimpanan server.');
            }
        }

        if ($user->isAdmin()) {
            $actor = AuditLogService::formatActor($user);
            $targetUser = AuditLogService::formatTargetUser($akun->user, $akun->user_id);

            $this->auditLogService->record(
                kategori: 'copytrade',
                aksi: 'lihat_dokumen',
                deskripsi: "{$actor} melihat berkas surat perjanjian copytrade akun {$akun->nomor_akun_trading} milik {$targetUser}.",
                subject: $akun,
                tingkat: 'info',
                user: $user,
                withCooldown: true
            );
        }

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Cache-Control'          => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'                 => 'no-cache',
                'Expires'                => '0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options'        => 'SAMEORIGIN',
            ]
        );
    }

    /**
     * Tampilkan (stream) berkas bukti modal klaim komisi privat terotentikasi.
     */
    public function showCommissionProof(Request $request, int $id): StreamedResponse
    {
        $claim = \App\Models\KlaimKomisi::with('affiliator')->findOrFail($id);
        $user = $request->user();

        // Otorisasi: Hanya Admin atau Affiliator pemohon yang dapat mengakses
        if (!$user || (!$user->isAdmin() && $user->id !== $claim->affiliator_id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat bukti modal klaim komisi ini.');
        }

        if (empty($claim->bukti_modal_path)) {
            abort(404, 'Berkas fisik bukti modal tidak ditemukan di penyimpanan server.');
        }

        $path = $claim->bukti_modal_path;
        if (!Storage::disk('local')->exists($path)) {
            $fallback = preg_replace('#^private/#', '', $path);
            if (Storage::disk('local')->exists($fallback)) {
                $path = $fallback;
            } else {
                abort(404, 'Berkas fisik bukti modal tidak ditemukan di penyimpanan server.');
            }
        }

        if ($user->isAdmin()) {
            $actor = AuditLogService::formatActor($user);
            $affiliatorTag = AuditLogService::formatTargetUser($claim->affiliator, $claim->affiliator_id);

            $this->auditLogService->record(
                kategori: 'komisi_afiliasi',
                aksi: 'lihat_dokumen',
                deskripsi: "{$actor} melihat berkas bukti modal klaim komisi {$claim->kode_klaim} milik {$affiliatorTag}.",
                subject: $claim,
                tingkat: 'info',
                user: $user,
                withCooldown: true
            );
        }

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Cache-Control'          => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'                 => 'no-cache',
                'Expires'                => '0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options'        => 'SAMEORIGIN',
            ]
        );
    }

    /**
     * Tampilkan (stream) berkas resi transfer bank klaim komisi privat terotentikasi.
     */
    public function showCommissionReceipt(Request $request, int $id): StreamedResponse
    {
        $claim = \App\Models\KlaimKomisi::with('affiliator')->findOrFail($id);
        $user = $request->user();

        // Otorisasi: Hanya Admin atau Affiliator pemohon yang dapat mengakses
        if (!$user || (!$user->isAdmin() && $user->id !== $claim->affiliator_id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat bukti transfer ini.');
        }

        if (empty($claim->bukti_transfer_path)) {
            abort(404, 'Berkas bukti transfer belum tersedia atau tidak ditemukan di penyimpanan server.');
        }

        $path = $claim->bukti_transfer_path;
        if (!Storage::disk('local')->exists($path)) {
            $fallback = preg_replace('#^private/#', '', $path);
            if (Storage::disk('local')->exists($fallback)) {
                $path = $fallback;
            } else {
                abort(404, 'Berkas bukti transfer tidak ditemukan di penyimpanan server.');
            }
        }

        if ($user->isAdmin()) {
            $actor = AuditLogService::formatActor($user);
            $affiliatorTag = AuditLogService::formatTargetUser($claim->affiliator, $claim->affiliator_id);

            $this->auditLogService->record(
                kategori: 'komisi_afiliasi',
                aksi: 'lihat_dokumen',
                deskripsi: "{$actor} melihat berkas bukti transfer bank klaim komisi {$claim->kode_klaim} milik {$affiliatorTag}.",
                subject: $claim,
                tingkat: 'info',
                user: $user,
                withCooldown: true
            );
        }

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Cache-Control'          => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'                 => 'no-cache',
                'Expires'                => '0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options'        => 'SAMEORIGIN',
            ]
        );
    }

    /**
     * Tampilkan (stream) berkas gambar materi promosi profit master.
     */
    public function showProfitImage(Request $request, int $id): StreamedResponse
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['administrator', 'member'], true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat materi promosi ini.');
        }

        $image = ProfitMasterPostImage::findOrFail($id);
        $path = $image->file_path;

        if (!Storage::disk('local')->exists($path)) {
            $fallback = preg_replace('#^private/#', '', $path);
            if (Storage::disk('local')->exists($fallback)) {
                $path = $fallback;
            } else {
                abort(404, 'Berkas fisik gambar materi promosi tidak ditemukan di penyimpanan server.');
            }
        }

        return Storage::disk('local')->response(
            $path,
            $image->file_name,
            [
                'Content-Type'           => $image->mime_type,
                'Cache-Control'          => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Tampilkan (stream) berkas lampiran gambar tiket bantuan privat terotentikasi.
     */
    public function showTiketAttachment(Request $request, int $id): StreamedResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Akses ditolak.');
        }

        $lampiran = TiketBantuanLampiran::with('balasan.tiket')->findOrFail($id);
        $tiket = $lampiran->balasan?->tiket;

        if (!$tiket) {
            abort(404, 'Tiket bantuan terkait tidak ditemukan.');
        }

        // Otorisasi: Hanya Admin atau Pemilik Tiket yang dapat mengakses
        if (!$user->isAdmin() && $user->id !== $tiket->user_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat lampiran tiket ini.');
        }

        $path = $lampiran->file_path;
        if (!Storage::disk('local')->exists($path)) {
            $fallback = preg_replace('#^private/#', '', $path);
            if (Storage::disk('local')->exists($fallback)) {
                $path = $fallback;
            } else {
                abort(404, 'Berkas fisik lampiran tiket tidak ditemukan di penyimpanan server.');
            }
        }

        return Storage::disk('local')->response(
            $path,
            $lampiran->file_name,
            [
                'Content-Type'           => $lampiran->mime_type,
                'Cache-Control'          => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Tampilkan (stream) berkas bukti transfer master fee privat terotentikasi.
     */
    public function showMasterFeeProof(Request $request, int $id): StreamedResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Akses ditolak.');
        }

        $laporan = \App\Models\LaporanProfitBulanan::with('user')->findOrFail($id);

        // Otorisasi: Hanya Admin atau Pemilik Laporan yang dapat mengakses (Afiliator TIDAK boleh)
        if (!$user->isAdmin() && $user->id !== $laporan->user_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat bukti transfer ini.');
        }

        $path = $laporan->bukti_transfer_path;
        if (!$path || !Storage::disk('local')->exists($path)) {
            $fallback = $path ? preg_replace('#^private/#', '', $path) : '';
            if ($fallback && Storage::disk('local')->exists($fallback)) {
                $path = $fallback;
            } else {
                abort(404, 'Berkas fisik bukti transfer tidak ditemukan di penyimpanan server.');
            }
        }

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Cache-Control'          => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Tampilkan (stream) screenshot Myfxbook privat terotentikasi.
     */
    public function showMyfxbookProof(Request $request, int $id): StreamedResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Akses ditolak.');
        }

        $laporan = \App\Models\LaporanProfitBulanan::with('user')->findOrFail($id);

        // Otorisasi: Hanya Admin atau Pemilik Laporan yang dapat mengakses (Afiliator TIDAK boleh)
        if (!$user->isAdmin() && $user->id !== $laporan->user_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat screenshot Myfxbook ini.');
        }

        $path = $laporan->bukti_myfxbook_path;
        if (!$path || !Storage::disk('local')->exists($path)) {
            $fallback = $path ? preg_replace('#^private/#', '', $path) : '';
            if ($fallback && Storage::disk('local')->exists($fallback)) {
                $path = $fallback;
            } else {
                abort(404, 'Berkas fisik screenshot Myfxbook tidak ditemukan di penyimpanan server.');
            }
        }

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Cache-Control'          => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Tampilkan (stream) screenshot bukti penarikan (WD) broker privat terotentikasi.
     */
    public function showWdProof(Request $request, int $id): StreamedResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Akses ditolak.');
        }

        $laporan = \App\Models\LaporanProfitBulanan::with('user')->findOrFail($id);

        // Otorisasi: Hanya Admin atau Pemilik Laporan yang dapat mengakses (Afiliator TIDAK boleh)
        if (!$user->isAdmin() && $user->id !== $laporan->user_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat screenshot penarikan broker ini.');
        }

        $path = $laporan->bukti_wd_path;
        if (!$path || !Storage::disk('local')->exists($path)) {
            $fallback = $path ? preg_replace('#^private/#', '', $path) : '';
            if ($fallback && Storage::disk('local')->exists($fallback)) {
                $path = $fallback;
            } else {
                abort(404, 'Berkas fisik screenshot penarikan broker tidak ditemukan di penyimpanan server.');
            }
        }

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Cache-Control'          => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
