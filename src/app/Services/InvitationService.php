<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Ambil daftar tautan undangan terpaginasi dengan pencarian dan filter server-side.
     *
     * @param array<string, mixed> $filters
     */
    public function getPaginatedInvitations(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Invitation::query();

        // Filter pencarian keyword
        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%");
            });
        }

        // Filter status
        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'active' => $query->where('is_active', true)
                    ->where(fn ($q) => $q->whereNull('expired_at')->orWhere('expired_at', '>', now()))
                    ->whereColumn('total_terpakai', '<', 'kuota_maksimal'),
                'expired'    => $query->whereNotNull('expired_at')->where('expired_at', '<=', now()),
                'quota_full' => $query->whereColumn('total_terpakai', '>=', 'kuota_maksimal'),
                default      => null,
            };
        }

        // Pengurutan (Sorting)
        $allowedSorts = ['invitation_id', 'label', 'total_terpakai', 'created_at', 'expired_at'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts, true) ? $filters['sort_by'] : 'invitation_id';
        $sortDirection = strtolower($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 10)));

        return $query->paginate($perPage)
            ->withQueryString()
            ->through(function (Invitation $inv): array {
                $isExpired = $inv->isExpired();
                $isQuotaFull = $inv->isQuotaFull();

                $statusKey = 'active';
                $statusLabel = 'Aktif';

                if (!$inv->is_active) {
                    $statusKey = 'inactive';
                    $statusLabel = 'Nonaktif';
                } elseif ($isQuotaFull) {
                    $statusKey = 'quota_full';
                    $statusLabel = 'Kuota Habis';
                } elseif ($isExpired) {
                    $statusKey = 'expired';
                    $statusLabel = 'Kedaluwarsa';
                }

                return [
                    'invitation_id'    => $inv->invitation_id,
                    'token'            => $inv->token,
                    'masked_token'     => substr($inv->token, 0, 8) . '...',
                    'full_url'         => url('/register?invite=' . $inv->token),
                    'label'            => $inv->label,
                    'kuota_maksimal'   => $inv->kuota_maksimal,
                    'total_terpakai'   => $inv->total_terpakai,
                    'usage_label'      => "{$inv->total_terpakai} / {$inv->kuota_maksimal}",
                    'is_active'        => $inv->is_active,
                    'status_key'       => $statusKey,
                    'status_label'     => $statusLabel,
                    'created_at_label' => tanggal_singkat($inv->created_at),
                    'expired_at_label' => $inv->expired_at ? tanggal_singkat($inv->expired_at) : 'Tanpa Batas',
                ];
            });
    }

    /**
     * Ambil seluruh daftar tautan undangan untuk tampilan admin.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInvitationsList(): array
    {
        $invitations = Invitation::query()
            ->latest('invitation_id')
            ->get();

        return $invitations->map(function (Invitation $inv): array {
            $isExpired = $inv->isExpired();
            $isQuotaFull = $inv->isQuotaFull();

            $statusKey = 'active';
            $statusLabel = 'Aktif';
            if (!$inv->is_active) {
                $statusKey = 'inactive';
                $statusLabel = 'Nonaktif';
            } elseif ($isQuotaFull) {
                $statusKey = 'quota_full';
                $statusLabel = 'Kuota Habis';
            } elseif ($isExpired) {
                $statusKey = 'expired';
                $statusLabel = 'Kedaluwarsa';
            }

            return [
                'invitation_id'    => $inv->invitation_id,
                'token'            => $inv->token,
                'masked_token'     => substr($inv->token, 0, 8) . '...',
                'full_url'         => url('/register?invite=' . $inv->token),
                'label'            => $inv->label,
                'kuota_maksimal'   => $inv->kuota_maksimal,
                'total_terpakai'   => $inv->total_terpakai,
                'usage_label'      => "{$inv->total_terpakai} / {$inv->kuota_maksimal}",
                'is_active'        => $inv->is_active,
                'status_key'       => $statusKey,
                'status_label'     => $statusLabel,
                'created_at_label' => tanggal_singkat($inv->created_at),
                'expired_at_label' => $inv->expired_at ? tanggal_singkat($inv->expired_at) : 'Tanpa Batas',
            ];
        })->toArray();
    }

    /**
     * Buat tautan undangan baru.
     *
     * @param array{label: string, kuota_maksimal?: int, expired_hours?: int} $data
     */
    public function createInvitation(array $data, ?User $creator = null): Invitation
    {
        return DB::transaction(function () use ($data, $creator): Invitation {
            $token = Str::random(32);

            $expiredAt = null;
            $hours = (int) ($data['expired_hours'] ?? 24);
            if ($hours > 0) {
                $expiredAt = now()->addHours($hours);
            }

            $invitation = Invitation::create([
                'token'          => $token,
                'label'          => $data['label'],
                'kuota_maksimal' => max(1, (int) ($data['kuota_maksimal'] ?? 1)),
                'total_terpakai' => 0,
                'is_active'      => true,
                'expired_at'     => $expiredAt,
                'created_by'     => $creator?->username ?? $creator?->email,
            ]);

            // Audit Log: Pembuatan tautan undangan
            $actor = AuditLogService::formatActor($creator);
            $this->auditLogService->record(
                kategori: 'undangan',
                aksi: 'buat_undangan',
                deskripsi: "{$actor} menerbitkan tautan undangan baru '{$invitation->label}' (kuota: {$invitation->kuota_maksimal}).",
                subject: $invitation,
                perubahan: [
                    'sesudah' => [
                        'label'          => $invitation->label,
                        'kuota_maksimal' => $invitation->kuota_maksimal,
                        'expired_at'     => $invitation->expired_at ? (string) $invitation->expired_at : null,
                    ],
                ],
                tingkat: 'info',
                user: $creator
            );

            return $invitation;
        });
    }

    /**
     * Validasi token undangan saat calon pengguna membuka halaman pendaftaran.
     *
     * @return array{valid: bool, message?: string, invitation?: array<string, mixed>}
     */
    public function validateToken(?string $token): array
    {
        if (empty($token)) {
            return [
                'valid'   => false,
                'message' => 'Tautan pendaftaran tidak menyertakan kode undangan yang valid.',
            ];
        }

        /** @var Invitation|null $invitation */
        $invitation = Invitation::query()
            ->where('token', $token)
            ->first();

        if (!$invitation || !$invitation->is_active) {
            return [
                'valid'   => false,
                'message' => 'Tautan undangan tidak ditemukan atau telah dinonaktifkan oleh Administrator.',
            ];
        }

        if ($invitation->isExpired()) {
            return [
                'valid'   => false,
                'message' => 'Masa berlaku tautan undangan ini telah kedaluwarsa.',
            ];
        }

        if ($invitation->isQuotaFull()) {
            return [
                'valid'   => false,
                'message' => 'Kuota pendaftaran untuk tautan undangan ini telah habis.',
            ];
        }

        return [
            'valid'      => true,
            'invitation' => [
                'invitation_id' => $invitation->invitation_id,
                'token'         => $invitation->token,
                'label'         => $invitation->label,
            ],
        ];
    }

    /**
     * Hapus / Nonaktifkan tautan undangan.
     */
    public function deleteInvitation(int $invitationId): void
    {
        $invitation = Invitation::findOrFail($invitationId);

        // Audit Log: Hapus undangan
        $this->auditLogService->record(
            kategori: 'undangan',
            aksi: 'hapus_undangan',
            deskripsi: "Penghapusan tautan undangan '{$invitation->label}' (#{$invitation->invitation_id}).",
            subject: $invitation,
            perubahan: [
                'sebelum' => [
                    'label'          => $invitation->label,
                    'total_terpakai' => $invitation->total_terpakai,
                ],
            ],
            tingkat: 'warning'
        );

        $invitation->delete();
    }

    /**
     * Hapus beberapa tautan undangan secara massal (batch delete).
     *
     * @param array<int> $invitationIds
     */
    public function batchDeleteInvitations(array $invitationIds): int
    {
        return DB::transaction(function () use ($invitationIds): int {
            $count = Invitation::whereIn('invitation_id', $invitationIds)->count();

            // Audit Log: Hapus undangan massal
            $this->auditLogService->record(
                kategori: 'undangan',
                aksi: 'hapus_undangan',
                deskripsi: "Penghapusan massal {$count} tautan undangan.",
                perubahan: [
                    'meta' => [
                        'invitation_ids' => $invitationIds,
                        'count'          => $count,
                    ],
                ],
                tingkat: 'warning'
            );

            $invitations = Invitation::whereIn('invitation_id', $invitationIds)->get();
            $invitations->each->delete();

            return $invitations->count();
        });
    }
}
