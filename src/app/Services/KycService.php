<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDokumen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class KycService
{
    public function __construct(
        private readonly DocumentQualityService $qualityService,
    ) {}

    /**
     * Unggah berkas dokumen identitas / perjanjian ke penyimpanan privat.
     */
    public function uploadDocument(User $user, UploadedFile $file, string $type): UserDokumen
    {
        if (!in_array($type, ['ktp', 'perjanjian'], true)) {
            throw new \InvalidArgumentException("Tipe dokumen tidak valid: {$type}");
        }

        $quality = $this->qualityService->inspect($file);

        $storedPath = $file->store("kyc_documents/{$user->id}", 'local');

        return UserDokumen::updateOrCreate(
            [
                'user_id'      => $user->id,
                'tipe_dokumen' => $type,
            ],
            [
                'file_path'          => $storedPath,
                'file_name'          => $file->getClientOriginalName(),
                'mime_type'          => $file->getClientMimeType() ?? $file->getMimeType(),
                'file_size'          => $file->getSize(),
                'status'             => 'pending',
                'catatan_admin'      => null,
                'is_quality_warning' => $quality['is_quality_warning'],
                'verified_at'        => null,
                'verified_by'        => null,
            ]
        );
    }

    /**
     * Unggah ulang berkas dokumen yang berstatus revision_needed.
     *
     * @throws ValidationException
     */
    public function reuploadDocument(User $user, UploadedFile $file, string $type): UserDokumen
    {
        $existing = UserDokumen::where('user_id', $user->id)
            ->where('tipe_dokumen', $type)
            ->first();

        if (!$existing) {
            return $this->uploadDocument($user, $file, $type);
        }

        if ($existing->status !== 'revision_needed' && $existing->status !== 'pending') {
            throw ValidationException::withMessages([
                $type => 'Berkas ini telah disetujui dan tidak dapat diunggah ulang kecuali diminta oleh Administrator.',
            ]);
        }

        // Hapus berkas fisik lama dari disk privat
        if ($existing->file_path && Storage::disk('local')->exists($existing->file_path)) {
            Storage::disk('local')->delete($existing->file_path);
        }

        $quality = $this->qualityService->inspect($file);
        $storedPath = $file->store("kyc_documents/{$user->id}", 'local');

        $existing->update([
            'file_path'          => $storedPath,
            'file_name'          => $file->getClientOriginalName(),
            'mime_type'          => $file->getClientMimeType() ?? $file->getMimeType(),
            'file_size'          => $file->getSize(),
            'status'             => 'pending',
            'catatan_admin'      => null,
            'is_quality_warning' => $quality['is_quality_warning'],
            'verified_at'        => null,
            'verified_by'        => null,
        ]);

        return $existing;
    }

    /**
     * Verifikasi dokumen oleh Administrator.
     *
     * @throws ValidationException
     */
    public function verifyDocument(UserDokumen $dokumen, string $status, ?string $catatanAdmin, User $verifier): UserDokumen
    {
        if (!in_array($status, ['approved', 'revision_needed', 'rejected'], true)) {
            throw new \InvalidArgumentException("Status verifikasi tidak valid: {$status}");
        }

        if ($status === 'revision_needed' && empty(trim((string) $catatanAdmin))) {
            throw ValidationException::withMessages([
                'catatan_admin' => 'Catatan admin wajib diisi ketika meminta revisi berkas kepada investor.',
            ]);
        }

        return DB::transaction(function () use ($dokumen, $status, $catatanAdmin, $verifier): UserDokumen {
            $dokumen->update([
                'status'        => $status,
                'catatan_admin' => $status === 'revision_needed' ? $catatanAdmin : ($status === 'approved' ? null : $catatanAdmin),
                'verified_at'   => now(),
                'verified_by'   => $verifier->id,
            ]);

            $user = $dokumen->user;

            // Jika seluruh dokumen (ktp dan perjanjian) telah approved, aktifkan user
            if ($user->isKycApproved()) {
                $user->update(['status' => 'active']);
            } elseif ($status === 'rejected') {
                $user->update(['status' => 'rejected']);
            } elseif ($user->status === 'active' && $status === 'revision_needed') {
                // Jika sebelumnya active tapi salah satu berkas diturunkan ke revisi
                $user->update(['status' => 'pending']);
            }

            return $dokumen;
        });
    }
}
