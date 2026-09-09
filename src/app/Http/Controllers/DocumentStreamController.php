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
}
