<?php

namespace App\Http\Controllers;

use App\Models\UserDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentStreamController extends Controller
{
    /**
     * Tampilkan (stream) berkas privat terotentikasi.
     */
    public function show(Request $request, int $id): StreamedResponse
    {
        $dokumen = UserDokumen::findOrFail($id);
        $user = $request->user();

        // Otorisasi: Hanya Admin atau Pemilik Dokumen yang dapat mengakses
        if (!$user || (!$user->isAdmin() && $user->id !== $dokumen->user_id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat berkas dokumen ini.');
        }

        if (!Storage::disk('local')->exists($dokumen->file_path)) {
            abort(404, 'Berkas fisik tidak ditemukan di penyimpanan server.');
        }

        return Storage::disk('local')->response(
            $dokumen->file_path,
            $dokumen->file_name,
            [
                'Cache-Control'          => 'no-cache, private, no-store, must-revalidate',
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
        $claim = \App\Models\KlaimReferral::findOrFail($id);
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

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Cache-Control'          => 'no-cache, private, no-store, must-revalidate',
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
        $akun = \App\Models\AkunCopytrade::findOrFail($id);
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

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Cache-Control'          => 'no-cache, private, no-store, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options'        => 'SAMEORIGIN',
            ]
        );
    }
}
