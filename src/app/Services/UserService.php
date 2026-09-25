<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Ambil data pengguna berpaginasi khusus role investor dan affiliator beserta metrik statistik.
     *
     * @param array<string, mixed> $filters
     * @return array{users: LengthAwarePaginator, stats: array<string, int>, filters: array<string, mixed>}
     */
    public function getUsersPaginated(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $roleFilter = trim((string) ($filters['role'] ?? ''));
        $statusFilter = trim((string) ($filters['status'] ?? ''));
        $activeFilter = trim((string) ($filters['is_active'] ?? ''));
        $sortField = trim((string) ($filters['sort_by'] ?? ($filters['sort'] ?? 'created_at')));
        $sortDirection = strtolower(trim((string) ($filters['sort_direction'] ?? ($filters['direction'] ?? 'desc')))) === 'asc' ? 'asc' : 'desc';
        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 10)));

        $allowedSorts = ['name', 'username', 'email', 'role', 'status', 'is_active', 'created_at'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }

        // Basis query: Khusus role investor dan affiliator (Opsi B)
        $baseQuery = User::query()->whereIn('role', ['investor', 'affiliator']);

        $stats = [
            'total_users'       => (clone $baseQuery)->count(),
            'total_investors'   => (clone $baseQuery)->where('role', 'investor')->count(),
            'total_affiliators' => (clone $baseQuery)->where('role', 'affiliator')->count(),
            'total_active'      => (clone $baseQuery)->where('is_active', true)->count(),
            'total_inactive'    => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        $query = (clone $baseQuery)->with([
            'ahliWaris',
            'dokumen',
            'akunCopytrades',
            'relasiUpline.affiliator',
            'relasiDownlines',
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if (in_array($roleFilter, ['investor', 'affiliator'], true)) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'active'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($activeFilter === 'active' || $activeFilter === '1') {
            $query->where('is_active', true);
        } elseif ($activeFilter === 'inactive' || $activeFilter === '0') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user) {
                return [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'username'        => $user->username,
                    'email'           => $user->email,
                    'phone'           => $user->phone ?? '-',
                    'nik'             => $user->nik,
                    'avatar'          => $user->avatar,
                    'role'            => $user->role,
                    'status'          => $user->status,
                    'is_active'       => (bool) $user->is_active,
                    'created_at'      => tanggal_waktu_indo($user->created_at),
                    'created_at_date' => $user->created_at ? tanggal_singkat($user->created_at) : '-',
                    'copytrade_count' => $user->akunCopytrades->count(),
                    'downlines_count' => $user->role === 'affiliator' ? $user->relasiDownlines->count() : 0,
                    'upline'          => $user->relasiUpline?->affiliator ? [
                        'name'          => $user->relasiUpline->affiliator->name,
                        'username'      => $user->relasiUpline->affiliator->username,
                        'referral_code' => $user->relasiUpline->affiliator->referral_code,
                    ] : null,
                ];
            });

        return [
            'users'   => $users,
            'stats'   => $stats,
            'filters' => [
                'search'         => $search,
                'role'           => $roleFilter,
                'status'         => $statusFilter,
                'is_active'      => $activeFilter,
                'sort_by'        => $sortField,
                'sort_direction' => $sortDirection,
                'per_page'       => $perPage,
            ],
        ];
    }

    /**
     * Ambil data lengkap profil pengguna untuk halaman detail.
     *
     * @return array<string, mixed>
     */
    public function getUserDetail(int $id): array
    {
        /** @var User $user */
        $user = User::query()
            ->whereIn('role', ['investor', 'affiliator'])
            ->with([
                'ahliWaris',
                'dokumen',
                'akunCopytrades',
                'relasiUpline.affiliator',
                'relasiDownlines.downline',
            ])
            ->findOrFail($id);

        $ktp = $user->dokumen->firstWhere('tipe_dokumen', 'ktp');
        $upline = $user->relasiUpline?->affiliator;

        $copytrades = $user->akunCopytrades->sortByDesc('created_at')->values()->map(function ($akun) {
            return [
                'id'                   => $akun->id,
                'tier'                 => $akun->tier,
                'broker'               => $akun->broker,
                'tipe_akun'            => $akun->tipe_akun,
                'nomor_akun_trading'   => $akun->nomor_akun_trading,
                'server_broker'        => $akun->server_broker,
                'modal_riil'           => (float) $akun->modal_riil,
                'modal_riil_formatted' => '$ ' . number_format((float) $akun->modal_riil, 2),
                'status'               => $akun->status,
                'tanggal_mulai'        => $akun->tanggal_mulai ? tanggal_indo($akun->tanggal_mulai) : '-',
                'verified_at'          => $akun->verified_at ? tanggal_waktu_indo($akun->verified_at, true) : '-',
                'copy_started_at'      => $akun->copy_started_at ? tanggal_waktu_indo($akun->copy_started_at, true) : '-',
                'url_mql5'             => $akun->url_mql5,
                'nama_bank'            => $akun->nama_bank ?? '-',
                'nomor_rekening'       => $akun->nomor_rekening ?? '-',
                'atas_nama_rekening'   => $akun->atas_nama_rekening ?? '-',
                'catatan_admin'        => $akun->catatan_admin,
                'agreement_url'        => $akun->surat_perjanjian_path ? route('documents.copytrade', $akun->id, false) : null,
            ];
        });

        $downlines = [];
        if ($user->role === 'affiliator') {
            $downlines = $user->relasiDownlines->sortByDesc('joined_at')->values()->map(function ($rel) {
                return [
                    'id'              => $rel->downline?->id,
                    'name'            => $rel->downline?->name ?? 'Pengguna',
                    'username'        => $rel->downline?->username ?? '-',
                    'email'           => $rel->downline?->email ?? '-',
                    'phone'           => $rel->downline?->phone ?? '-',
                    'status'          => $rel->downline?->status ?? 'pending',
                    'is_active'       => (bool) ($rel->downline?->is_active ?? true),
                    'joined_at'       => $rel->joined_at ? tanggal_waktu_indo($rel->joined_at, true) : '-',
                    'joined_at_short' => $rel->joined_at ? tanggal_singkat($rel->joined_at) : '-',
                ];
            })->all();
        }

        return [
            'id'              => $user->id,
            'name'            => $user->name,
            'username'        => $user->username,
            'email'           => $user->email,
            'nik'             => $user->nik ?? '-',
            'phone'           => $user->phone ?? '-',
            'alamat'          => $user->alamat ?? '-',
            'tempat_lahir'    => $user->tempat_lahir ?? '-',
            'tanggal_lahir'   => $user->tanggal_lahir ? tanggal_indo($user->tanggal_lahir) : '-',
            'pekerjaan'       => $user->pekerjaan ?? '-',
            'avatar'          => $user->avatar,
            'role'            => $user->role,
            'status'          => $user->status,
            'is_active'       => (bool) $user->is_active,
            'referral_code'   => $user->referral_code ?? '-',
            'terms_agreed_at' => $user->terms_agreed_at ? tanggal_waktu_indo($user->terms_agreed_at, true) : '-',
            'ip_address'      => $user->ip_address ?? '-',
            'created_at'      => $user->created_at ? tanggal_waktu_indo($user->created_at, true) : '-',
            'created_at_date' => $user->created_at ? tanggal_indo($user->created_at) : '-',
            'ahli_waris'      => $user->ahliWaris ? [
                'nama'              => $user->ahliWaris->nama,
                'hubungan_keluarga' => $user->ahliWaris->hubungan_keluarga,
                'no_hp'             => $user->ahliWaris->no_hp,
                'alamat'            => $user->ahliWaris->alamat ?? '-',
            ] : null,
            'ktp' => $ktp ? [
                'id'                 => $ktp->id,
                'file_name'          => $ktp->file_name,
                'file_size'          => $ktp->file_size,
                'mime_type'          => $ktp->mime_type,
                'status'             => $ktp->status,
                'catatan_admin'      => $ktp->catatan_admin,
                'is_quality_warning' => (bool) $ktp->is_quality_warning,
                'verified_at'        => $ktp->verified_at ? tanggal_waktu_indo($ktp->verified_at, true) : null,
                'preview_url'        => route('documents.show', $ktp->id, false),
            ] : null,
            'copytrades' => $copytrades,
            'upline'     => $upline ? [
                'id'            => $upline->id,
                'name'          => $upline->name,
                'username'      => $upline->username,
                'email'         => $upline->email,
                'phone'         => $upline->phone ?? '-',
                'referral_code' => $upline->referral_code ?? '-',
                'joined_at'     => $user->relasiUpline?->joined_at ? tanggal_waktu_indo($user->relasiUpline->joined_at, true) : '-',
            ] : null,
            'downlines' => $downlines,
        ];
    }

    /**
     * Alihkan (toggle) status aktif/non-aktif akun pengguna.
     * Bila dinonaktifkan, seluruh sesi aktif pengguna akan diputus seketika.
     *
     * @throws DomainException
     */
    public function toggleUserStatus(int $userId, int $adminId): User
    {
        return DB::transaction(function () use ($userId, $adminId): User {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($userId);

            if ($user->isAdmin()) {
                throw new DomainException('Akun administrator tidak dapat dinonaktifkan.');
            }

            if ($user->id === $adminId) {
                throw new DomainException('Anda tidak dapat mengubah status akun Anda sendiri.');
            }

            $user->is_active = !$user->is_active;
            $user->save();

            // Bila akun dinonaktifkan, putuskan seluruh sesi aktif pengguna seketika
            if (!$user->is_active) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            return $user;
        });
    }
}
