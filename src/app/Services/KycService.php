<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\User;
use App\Models\UserDokumen;
use App\Notifications\CopytradeAccountStatusChangedNotification;
use App\Notifications\CopytradeAccountUpdatedNotification;
use App\Notifications\KycDocumentReuploadedNotification;
use App\Notifications\KycDocumentStatusChangedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

        $this->syncUserStatus($user);

        // Notifikasi ke seluruh administrator
        $admins = User::where('role', 'administrator')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new KycDocumentReuploadedNotification($user, $existing));
        }

        return $existing;
    }

    /**
     * Verifikasi dokumen oleh Administrator.
     *
     * @throws ValidationException
     */
    public function verifyDocument(UserDokumen $dokumen, string $status, ?string $catatanAdmin, User $verifier): UserDokumen
    {
        if (!in_array($status, ['approved', 'revision_needed'], true)) {
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
                'catatan_admin' => $status === 'revision_needed' ? $catatanAdmin : null,
                'verified_at'   => now(),
                'verified_by'   => $verifier->id,
            ]);

            $user = $dokumen->user;

            $this->syncUserStatus($user);

            // Kirim notifikasi in-app ke investor
            $user->notify(new KycDocumentStatusChangedNotification($dokumen, $status, $catatanAdmin));

            return $dokumen;
        });
    }

    /**
     * Verifikasi akun copy trade oleh Administrator.
     *
     * @throws ValidationException
     */
    public function verifyAccount(AkunCopytrade $akun, string $status, ?string $catatanAdmin, User $verifier): AkunCopytrade
    {
        if (!in_array($status, ['active', 'rejected'], true)) {
            throw new \InvalidArgumentException("Status akun copy trade tidak valid: {$status}");
        }

        if ($status === 'rejected' && empty(trim((string) $catatanAdmin))) {
            throw ValidationException::withMessages([
                'catatan_admin' => 'Catatan admin wajib diisi ketika menolak akun copy trade.',
            ]);
        }

        return DB::transaction(function () use ($akun, $status, $catatanAdmin): AkunCopytrade {
            $akun->update([
                'status'        => $status,
                'catatan_admin' => $status === 'active' ? ($catatanAdmin ?: null) : $catatanAdmin,
            ]);

            $this->syncUserStatus($akun->user);

            // Kirim notifikasi in-app ke investor
            $akun->user->notify(new CopytradeAccountStatusChangedNotification($akun, $status, $catatanAdmin));

            return $akun;
        });
    }

    /**
     * Sinkronisasi status akun pengguna (users.status) berdasarkan kelengkapan berkas KYC dan validasi akun copy trade.
     */
    public function syncUserStatus(User $user): User
    {
        $docsApproved = $user->isKycApproved();
        $akun = $user->akunCopytrade()->first();

        // Jika investor memiliki akun copy trade
        if ($akun) {
            $isFullyApproved = $docsApproved && $akun->status === 'active';

            if ($isFullyApproved) {
                if ($user->status !== 'active') {
                    $user->update(['status' => 'active']);
                }
            } else {
                // Jika berkas belum lengkap atau akun copy trade belum aktif (pending / rejected)
                if ($user->status === 'active') {
                    $user->update(['status' => 'pending']);
                }
            }
        } else {
            // Pengguna tanpa akun copy trade (misalnya affiliator atau pendaftaran tahap awal)
            if ($docsApproved) {
                if ($user->status !== 'active') {
                    $user->update(['status' => 'active']);
                }
            } elseif ($user->status === 'active') {
                $user->update(['status' => 'pending']);
            }
        }

        return $user;
    }

    /**
     * Pembaruan data akun copy trade & rekening oleh Investor (ketika pending atau rejected).
     *
     * @throws ValidationException
     */
    public function updateCopytradeAccount(User $user, array $data): AkunCopytrade
    {
        $akun = $user->akunCopytrade;

        if (!$akun) {
            throw new \RuntimeException('Data akun copy trade tidak ditemukan.');
        }

        if ($akun->status === 'active') {
            throw ValidationException::withMessages([
                'nomor_akun_trading' => 'Akun copy trade yang sudah aktif dan terverifikasi tidak dapat diubah secara langsung. Silakan hubungi Administrator.',
            ]);
        }

        return DB::transaction(function () use ($akun, $data, $user): AkunCopytrade {
            $updateData = [
                'nomor_akun_trading' => $data['nomor_akun_trading'],
                'server_broker'      => $data['server_broker'] ?? $akun->server_broker,
                'estimasi_modal'     => $data['estimasi_modal'],
                'nama_bank'          => $data['nama_bank'],
                'nomor_rekening'     => $data['nomor_rekening'],
                'atas_nama_rekening' => $data['atas_nama_rekening'],
                'status'             => 'pending',
                'catatan_admin'      => null,
            ];

            if (!empty($data['master_password'])) {
                $updateData['master_password'] = $data['master_password'];
            }

            if (!empty($data['investor_password'])) {
                $updateData['investor_password'] = $data['investor_password'];
            }

            $akun->update($updateData);

            $this->syncUserStatus($akun->user);

            // Notifikasi ke seluruh administrator
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CopytradeAccountUpdatedNotification($user, $akun, 'update'));
            }

            return $akun;
        });
    }

    /**
     * Konfirmasi follow master copy trade oleh investor untuk memicu peninjauan ulang.
     */
    public function confirmFollowMaster(User $user): AkunCopytrade
    {
        $akun = $user->akunCopytrade;

        if (!$akun) {
            throw new \RuntimeException('Data akun copy trade tidak ditemukan.');
        }

        if ($akun->status !== 'active') {
            $akun->update([
                'status'        => 'pending',
                'catatan_admin' => null,
            ]);

            $this->syncUserStatus($user);

            // Notifikasi ke seluruh administrator
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CopytradeAccountUpdatedNotification($user, $akun, 'confirm_follow'));
            }
        }

        return $akun;
    }
}
