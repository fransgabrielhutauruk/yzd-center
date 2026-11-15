<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\User;
use App\Models\UserDokumen;
use App\Notifications\KycDocumentReuploadedNotification;
use App\Notifications\KycDocumentStatusChangedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KycService
{
    public function __construct(
        private readonly DocumentQualityService $qualityService,
        private readonly AuditLogService $auditLogService,
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

        if ($existing->status === 'approved') {
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

        if ($type === 'perjanjian') {
            AkunCopytrade::where('user_id', $user->id)
                ->where('status', '!=', 'active')
                ->latest('id')
                ->first()
                ?->update(['surat_perjanjian_path' => $storedPath]);
        }

        $this->syncUserStatus($user);

        // Audit Log: Unggah ulang dokumen KYC
        $this->auditLogService->record(
            kategori: 'kyc_dokumen',
            aksi: 'unggah_ulang_kyc',
            deskripsi: "Pengguna {$user->name} (@{$user->username}) mengunggah ulang dokumen {$type}.",
            subject: $existing,
            perubahan: [
                'sesudah' => [
                    'tipe_dokumen'       => $type,
                    'file_name'          => $file->getClientOriginalName(),
                    'file_size'          => $file->getSize(),
                    'is_quality_warning' => $quality['is_quality_warning'],
                ],
            ],
            tingkat: 'info',
            user: $user
        );

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
            $sebelumStatus = $dokumen->status;
            $sebelumCatatan = $dokumen->catatan_admin;

            $dokumen->update([
                'status'        => $status,
                'catatan_admin' => $status === 'revision_needed' ? $catatanAdmin : null,
                'verified_at'   => now(),
                'verified_by'   => $verifier->id,
            ]);

            $user = $dokumen->user;

            $this->syncUserStatus($user);

            // Audit Log: Verifikasi dokumen KYC oleh admin
            $statusLabel = $status === 'approved' ? 'Disetujui' : 'Perlu Revisi';
            $actor = AuditLogService::formatActor($verifier);
            $target = AuditLogService::formatTargetUser($user, $dokumen->user_id);
            $this->auditLogService->record(
                kategori: 'kyc_dokumen',
                aksi: 'verifikasi_kyc',
                deskripsi: "{$actor} memverifikasi dokumen {$dokumen->tipe_dokumen} milik {$target} dengan status '{$statusLabel}'." . ($status === 'revision_needed' ? " Catatan: {$catatanAdmin}" : ''),
                subject: $dokumen,
                perubahan: [
                    'sebelum' => [
                        'status'        => $sebelumStatus,
                        'catatan_admin' => $sebelumCatatan,
                    ],
                    'sesudah' => [
                        'status'        => $status,
                        'catatan_admin' => $status === 'revision_needed' ? $catatanAdmin : null,
                    ],
                    'meta' => [
                        'user_id'      => $user->id,
                        'user_name'    => $user->name,
                        'tipe_dokumen' => $dokumen->tipe_dokumen,
                    ],
                ],
                tingkat: $status === 'revision_needed' ? 'warning' : 'info',
                user: $verifier
            );

            // Kirim notifikasi in-app ke investor
            $user->notify(new KycDocumentStatusChangedNotification($dokumen, $status, $catatanAdmin));

            return $dokumen;
        });
    }

    /**
     * Sinkronisasi status akun pengguna (users.status) berdasarkan kelengkapan berkas KYC.
     */
    public function syncUserStatus(User $user): User
    {
        $docsApproved = $user->isKycApproved();

        if ($docsApproved) {
            if ($user->status !== 'active') {
                $user->update(['status' => 'active']);
            }
        } elseif ($user->status === 'active') {
            $user->update(['status' => 'pending']);
        }

        return $user;
    }

    /**
     * Ekspor data verifikasi pendaftaran / antrean KYC ke format XLSX.
     *
     * @param array<string, mixed> $filters
     */
    public function exportRegistrationsXlsx(array $filters = []): BinaryFileResponse
    {
        $columns = [
            ['label' => 'ID', 'width' => 8, 'align' => 'center'],
            ['label' => 'Tanggal Daftar', 'width' => 20],
            ['label' => 'Nama Lengkap', 'width' => 25],
            ['label' => 'Username', 'width' => 18],
            ['label' => 'Email', 'width' => 25],
            ['label' => 'No. Telepon', 'width' => 18],
            ['label' => 'NIK', 'width' => 20, 'type' => 'text'],
            ['label' => 'Tempat/Tgl Lahir', 'width' => 25],
            ['label' => 'Pekerjaan', 'width' => 20],
            ['label' => 'Alamat', 'width' => 35],
            ['label' => 'Upline Referral', 'width' => 25],
            ['label' => 'Nama Ahli Waris', 'width' => 22],
            ['label' => 'Hubungan Ahli Waris', 'width' => 20],
            ['label' => 'No. HP Ahli Waris', 'width' => 18],
            ['label' => 'Status Berkas KTP', 'width' => 20, 'align' => 'center'],
            ['label' => 'Catatan Admin KTP', 'width' => 35],
        ];

        $search = trim((string) ($filters['search'] ?? ''));
        $statusFilter = trim((string) ($filters['status'] ?? ''));
        $sortField = trim((string) ($filters['sort_by'] ?? ($filters['sort'] ?? 'created_at')));
        $sortDirection = strtolower(trim((string) ($filters['sort_direction'] ?? ($filters['direction'] ?? 'desc')))) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'email', 'created_at', 'status'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }

        $query = User::query()
            ->where('role', 'member')
            ->where('status', '!=', 'active')
            ->with(['ahliWaris', 'dokumen', 'relasiUpline.affiliator']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereHas('relasiUpline.affiliator', function ($uplineQuery) use ($search) {
                        $uplineQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('referral_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($statusFilter !== '') {
            if ($statusFilter === 'pending') {
                $query->whereDoesntHave('dokumen', function ($q) {
                    $q->where('status', 'revision_needed');
                });
            } elseif ($statusFilter === 'revision_needed') {
                $query->whereHas('dokumen', function ($q) {
                    $q->where('status', 'revision_needed');
                });
            }
        }

        $query->orderBy($sortField, $sortDirection);

        // Audit Log
        $actor = Auth::user();
        if ($actor) {
            $this->auditLogService->record(
                kategori: 'kyc',
                aksi: 'ekspor_data',
                deskripsi: AuditLogService::formatActor($actor) . ' mengekspor data verifikasi pendaftaran KYC ke format XLSX.',
                tingkat: 'info',
                user: $actor
            );
        }

        $xlsxService = app(XlsxExportService::class);

        return $xlsxService->download('verifikasi_pendaftaran_kyc_yzd', 'KYC', $columns, function ($addRow) use ($query): void {
            $query->chunk(500, function ($users) use ($addRow): void {
                foreach ($users as $user) {
                    $ktp = $user->dokumen->firstWhere('tipe_dokumen', 'ktp');
                    $upline = $user->relasiUpline?->affiliator;
                    $ahliWaris = $user->ahliWaris;

                    $ttl = ($user->tempat_lahir ?? '-') . ' / ' . ($user->tanggal_lahir ? $user->tanggal_lahir->format('Y-m-d') : '-');

                    $statusKtp = match ($ktp?->status) {
                        'approved'        => 'Disetujui',
                        'revision_needed' => 'Perlu Revisi',
                        'rejected'        => 'Ditolak',
                        'pending'         => 'Menunggu Verifikasi',
                        default           => 'Belum Diunggah',
                    };

                    $addRow([
                        (string) $user->id,
                        $user->created_at ? $user->created_at->format('Y-m-d H:i') : '-',
                        $user->name,
                        '@' . $user->username,
                        $user->email,
                        $user->phone ?? '-',
                        (string) ($user->nik ?? '-'),
                        $ttl,
                        $user->pekerjaan ?? '-',
                        $user->alamat ?? '-',
                        $upline ? "{$upline->name} (@{$upline->username})" : '-',
                        $ahliWaris?->nama ?? '-',
                        $ahliWaris?->hubungan_keluarga ?? '-',
                        $ahliWaris?->no_hp ?? '-',
                        $statusKtp,
                        $ktp?->catatan_admin ?? '-',
                    ]);
                }
            });
        });
    }
}
