<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationService
{
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
                'invitation_id'     => $inv->invitation_id,
                'token'             => $inv->token,
                'masked_token'      => substr($inv->token, 0, 8) . '...',
                'full_url'          => url('/register?invite=' . $inv->token),
                'label'             => $inv->label,
                'target_role'       => $inv->target_role,
                'target_role_label' => ucfirst($inv->target_role),
                'kuota_maksimal'    => $inv->kuota_maksimal,
                'total_terpakai'    => $inv->total_terpakai,
                'usage_label'       => "{$inv->total_terpakai} / {$inv->kuota_maksimal}",
                'is_active'         => $inv->is_active,
                'status_key'        => $statusKey,
                'status_label'      => $statusLabel,
                'created_at_label'  => tanggal_singkat($inv->created_at),
                'expired_at_label'  => $inv->expired_at ? tanggal_singkat($inv->expired_at) : 'Tanpa Batas',
            ];
        })->toArray();
    }

    /**
     * Buat tautan undangan baru.
     *
     * @param array{label: string, target_role: string, kuota_maksimal?: int, expired_hours?: int} $data
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

            return Invitation::create([
                'token'          => $token,
                'label'          => $data['label'],
                'target_role'    => $data['target_role'] ?? 'investor',
                'kuota_maksimal' => max(1, (int) ($data['kuota_maksimal'] ?? 1)),
                'total_terpakai' => 0,
                'is_active'      => true,
                'expired_at'     => $expiredAt,
                'created_by'     => $creator?->username ?? $creator?->email,
            ]);
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
                'invitation_id'     => $invitation->invitation_id,
                'token'             => $invitation->token,
                'label'             => $invitation->label,
                'target_role'       => $invitation->target_role,
                'target_role_label' => ucfirst($invitation->target_role),
            ],
        ];
    }

    /**
     * Hapus / Nonaktifkan tautan undangan.
     */
    public function deleteInvitation(int $invitationId): void
    {
        $invitation = Invitation::findOrFail($invitationId);
        $invitation->delete();
    }
}
