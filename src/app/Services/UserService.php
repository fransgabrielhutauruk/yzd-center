<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserService
{
    public function __construct(
        private readonly ReferralService $referralService,
        private readonly AuditLogService $auditLogService,
    ) {}

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

        $allowedSorts = ['name', 'username', 'email', 'role', 'status', 'is_active', 'created_at', 'created_at_date', 'copytrade_count'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }

        if ($sortField === 'created_at_date') {
            $sortField = 'created_at';
        }

        // Basis query: Khusus role member
        $baseQuery = User::query()->where('role', 'member');

        $stats = [
            'total_users'          => (clone $baseQuery)->count(),
            'total_investors'      => User::activeInvestors()->count(),
            'total_affiliators'    => User::activeAffiliators()->count(),
            'total_active'         => (clone $baseQuery)->where('is_active', true)->count(),
            'total_inactive'       => (clone $baseQuery)->where('is_active', false)->count(),
            'inactive_investors'   => (clone $baseQuery)->where('is_active', false)->whereHas('akunCopytrade')->count(),
            'inactive_affiliators' => (clone $baseQuery)->where('is_active', false)->whereHas('relasiDownlines')->count(),
        ];

        $query = (clone $baseQuery)->with([
            'ahliWaris',
            'dokumen',
            'akunCopytrades',
            'relasiUpline.affiliator',
            'relasiDownlines',
        ])->withCount([
            'akunCopytrades as active_copytrade_count' => function ($q) {
                $q->where('status', 'active');
            },
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

        if ($roleFilter === 'investor') {
            $query->whereHas('akunCopytrade', fn ($q) => $q->where('status', 'active'));
        } elseif ($roleFilter === 'affiliator') {
            $query->whereHas('relasiDownlines.downline', fn ($q) => $q->where('is_active', true));
        } elseif ($roleFilter === 'member') {
            $query->whereDoesntHave('akunCopytrade', fn ($q) => $q->where('status', 'active'))
                ->whereDoesntHave('relasiDownlines.downline', fn ($q) => $q->where('is_active', true));
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'active'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($activeFilter === 'active' || $activeFilter === '1') {
            $query->where('is_active', true);
        } elseif ($activeFilter === 'inactive' || $activeFilter === '0') {
            $query->where('is_active', false);
        }

        if ($sortField === 'copytrade_count') {
            $query->orderBy('active_copytrade_count', $sortDirection)
                ->orderBy('created_at', 'desc');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $users = $query->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user) {
                return [
                    'id'                      => $user->id,
                    'name'                    => $user->name,
                    'username'                => $user->username,
                    'email'                   => $user->email,
                    'phone'                   => $user->phone ?? '-',
                    'nik'                     => $user->nik,
                    'avatar'                  => $user->avatar,
                    'role'                    => $user->role,
                    'derived_labels'          => $user->getDerivedLabels(),
                    'status'                  => $user->status,
                    'is_active'               => (bool) $user->is_active,
                    'referral_code'           => $user->referral_code,
                    'created_at'              => tanggal_waktu_indo($user->created_at),
                    'created_at_time'         => tanggal_waktu_indo($user->created_at, true),
                    'created_at_date'         => $user->created_at ? tanggal_singkat($user->created_at) : '-',
                    'copytrade_count'         => $user->akunCopytrades->count(),
                    'active_copytrade_count'  => (int) ($user->active_copytrade_count ?? $user->akunCopytrades->where('status', 'active')->count()),
                    'pending_copytrade_count' => $user->akunCopytrades->where('status', 'pending')->count(),
                    'downlines_count'         => $user->relasiDownlines->count(),
                    'upline'                  => $user->relasiUpline?->affiliator ? [
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
            ->where('role', 'member')
            ->with([
                'ahliWaris',
                'dokumen',
                'akunCopytrades',
                'relasiUpline.affiliator',
                'relasiDownlines.downline',
            ])
            ->findOrFail($id);

        $ktp = $user->dokumen->firstWhere('tipe_dokumen', 'ktp');
        $perjanjianDokumen = $user->dokumen->firstWhere('tipe_dokumen', 'perjanjian');
        $akunWithPerjanjian = $user->akunCopytrades->first(fn ($a) => !empty($a->surat_perjanjian_path));
        $bankAccount = $user->akunCopytrades->first(fn ($a) => !empty($a->nomor_rekening));
        $upline = $user->relasiUpline?->affiliator;

        $suratPerjanjian = null;
        if ($akunWithPerjanjian) {
            $suratPerjanjian = [
                'id'          => $akunWithPerjanjian->id,
                'file_name'   => basename($akunWithPerjanjian->surat_perjanjian_path),
                'preview_url' => route('documents.copytrade', $akunWithPerjanjian->id, false),
                'mime_type'   => strtolower(pathinfo($akunWithPerjanjian->surat_perjanjian_path, PATHINFO_EXTENSION)) === 'pdf' ? 'application/pdf' : 'image/jpeg',
                'is_pdf'      => strtolower(pathinfo($akunWithPerjanjian->surat_perjanjian_path, PATHINFO_EXTENSION)) === 'pdf',
                'verified_at' => $akunWithPerjanjian->verified_at ? tanggal_waktu_indo($akunWithPerjanjian->verified_at) : null,
            ];
        } elseif ($perjanjianDokumen) {
            $suratPerjanjian = [
                'id'          => $perjanjianDokumen->id,
                'file_name'   => $perjanjianDokumen->file_name,
                'preview_url' => route('documents.show', $perjanjianDokumen->id, false),
                'mime_type'   => $perjanjianDokumen->mime_type ?? 'application/pdf',
                'is_pdf'      => ($perjanjianDokumen->mime_type ?? '') === 'application/pdf' || str_ends_with(strtolower($perjanjianDokumen->file_name), '.pdf'),
                'verified_at' => $perjanjianDokumen->verified_at ? tanggal_waktu_indo($perjanjianDokumen->verified_at, true) : null,
            ];
        }

        $rekeningBank = $bankAccount ? [
            'nama_bank'          => $bankAccount->nama_bank,
            'nomor_rekening'     => $bankAccount->nomor_rekening,
            'atas_nama_rekening' => $bankAccount->atas_nama_rekening,
        ] : null;

        $copytrades = $user->akunCopytrades
            ->where('status', 'active')
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($akun) {
                return [
                    'id'                   => $akun->id,
                    'tier'                 => $akun->tier,
                    'broker'               => $akun->broker,
                    'tipe_akun'            => $akun->tipe_akun,
                    'nomor_akun_trading'   => $akun->nomor_akun_trading,
                    'modal_riil'           => (float) $akun->modal_riil,
                    'modal_riil_formatted' => '$ ' . number_format((float) $akun->modal_riil, 2),
                    'status'               => $akun->status,
                    'tanggal_mulai'        => $akun->tanggal_mulai ? tanggal_singkat($akun->tanggal_mulai) : '-',
                ];
            });

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
            'derived_labels'  => $user->getDerivedLabels(),
            'status'          => $user->status,
            'is_active'       => (bool) $user->is_active,
            'referral_code'   => $user->referral_code ?? '-',
            'terms_agreed_at' => $user->terms_agreed_at ? tanggal_waktu_indo($user->terms_agreed_at, true) : '-',
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
            'surat_perjanjian' => $suratPerjanjian,
            'rekening_bank'    => $rekeningBank,
            'copytrades'       => $copytrades,
            'upline'           => $upline ? [
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

            $sebelumActive = $user->is_active;
            $user->is_active = !$user->is_active;
            $user->save();

            // Bila akun dinonaktifkan, putuskan seluruh sesi aktif pengguna seketika
            if (!$user->is_active) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            // Audit Log: Ubah status pengguna (aktif/nonaktif)
            $admin = User::find($adminId);
            $actor = AuditLogService::formatActor($admin);
            $target = AuditLogService::formatTargetUser($user, $user->id);
            $statusLabel = $user->is_active ? 'diaktifkan' : 'dinonaktifkan (suspend)';
            $this->auditLogService->record(
                kategori: 'pengguna',
                aksi: 'ubah_status_user',
                deskripsi: "{$actor} mengubah status akun {$user->name} ({$target}) menjadi {$statusLabel}.",
                subject: $user,
                perubahan: [
                    'sebelum' => ['is_active' => $sebelumActive],
                    'sesudah' => ['is_active' => $user->is_active],
                ],
                tingkat: $user->is_active ? 'info' : 'warning',
                user: $admin
            );

            return $user;
        });
    }

    /**
     * Ekspor data manajemen pengguna ke format XLSX.
     *
     * @param array<string, mixed> $filters
     */
    public function exportXlsx(array $filters = []): BinaryFileResponse
    {
        $columns = [
            ['label' => 'ID', 'width' => 8, 'align' => 'center'],
            ['label' => 'Tanggal Terdaftar', 'width' => 20],
            ['label' => 'Nama Lengkap', 'width' => 25],
            ['label' => 'Username', 'width' => 18],
            ['label' => 'Email', 'width' => 25],
            ['label' => 'No. Telepon', 'width' => 18],
            ['label' => 'NIK', 'width' => 20, 'type' => 'text'],
            ['label' => 'Label Peran', 'width' => 22, 'align' => 'center'],
            ['label' => 'Kode Referral', 'width' => 16, 'align' => 'center'],
            ['label' => 'Nama Upline', 'width' => 24],
            ['label' => 'Username Upline', 'width' => 18],
            ['label' => 'Akun Copytrade Aktif', 'width' => 20, 'align' => 'center', 'type' => 'number'],
            ['label' => 'Total Downline', 'width' => 16, 'align' => 'center', 'type' => 'number'],
            ['label' => 'Status KYC', 'width' => 16, 'align' => 'center'],
            ['label' => 'Status Akses', 'width' => 16, 'align' => 'center'],
        ];

        $search = trim((string) ($filters['search'] ?? ''));
        $roleFilter = trim((string) ($filters['role'] ?? ''));
        $statusFilter = trim((string) ($filters['status'] ?? ''));
        $activeFilter = trim((string) ($filters['is_active'] ?? ''));
        $sortField = trim((string) ($filters['sort_by'] ?? ($filters['sort'] ?? 'created_at')));
        $sortDirection = strtolower(trim((string) ($filters['sort_direction'] ?? ($filters['direction'] ?? 'desc')))) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'username', 'email', 'role', 'status', 'is_active', 'created_at', 'created_at_date', 'copytrade_count'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }
        if ($sortField === 'created_at_date') {
            $sortField = 'created_at';
        }

        $query = User::query()
            ->where('role', 'member')
            ->with([
                'ahliWaris',
                'dokumen',
                'akunCopytrades',
                'relasiUpline.affiliator',
                'relasiDownlines',
            ])->withCount([
                'akunCopytrades as active_copytrade_count' => function ($q) {
                    $q->where('status', 'active');
                },
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

        if ($roleFilter === 'investor') {
            $query->whereHas('akunCopytrade', fn ($q) => $q->where('status', 'active'));
        } elseif ($roleFilter === 'affiliator') {
            $query->whereHas('relasiDownlines.downline', fn ($q) => $q->where('is_active', true));
        } elseif ($roleFilter === 'member') {
            $query->whereDoesntHave('akunCopytrade', fn ($q) => $q->where('status', 'active'))
                ->whereDoesntHave('relasiDownlines.downline', fn ($q) => $q->where('is_active', true));
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'active'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($activeFilter === 'active' || $activeFilter === '1') {
            $query->where('is_active', true);
        } elseif ($activeFilter === 'inactive' || $activeFilter === '0') {
            $query->where('is_active', false);
        }

        if ($sortField === 'copytrade_count') {
            $query->orderBy('active_copytrade_count', $sortDirection)
                ->orderBy('created_at', 'desc');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        // Audit Log
        $actor = Auth::user();
        if ($actor) {
            $this->auditLogService->record(
                kategori: 'pengguna',
                aksi: 'ekspor_data',
                deskripsi: AuditLogService::formatActor($actor) . ' mengekspor data manajemen pengguna ke format XLSX.',
                tingkat: 'info',
                user: $actor
            );
        }

        $xlsxService = app(XlsxExportService::class);

        return $xlsxService->download('data_pengguna_yzd', 'Pengguna', $columns, function ($addRow) use ($query): void {
            $query->chunk(500, function ($users) use ($addRow): void {
                foreach ($users as $user) {
                    $labels = $user->getDerivedLabels();
                    $roleLabel = !empty($labels) ? implode(', ', $labels) : 'Member';
                    $upline = $user->relasiUpline?->affiliator;
                    $activeCopytrade = (int) ($user->active_copytrade_count ?? $user->akunCopytrades->where('status', 'active')->count());
                    $totalDownline = $user->relasiDownlines->count();

                    $addRow([
                        (string) $user->id,
                        $user->created_at ? $user->created_at->format('Y-m-d H:i') : '-',
                        $user->name,
                        '@' . $user->username,
                        $user->email,
                        $user->phone ?? '-',
                        (string) ($user->nik ?? '-'),
                        $roleLabel,
                        $user->referral_code ?? '-',
                        $upline?->name ?? '-',
                        $upline?->username ? '@' . $upline->username : '-',
                        $activeCopytrade,
                        $totalDownline,
                        strtoupper($user->status),
                        $user->is_active ? 'Aktif' : 'Dinonaktifkan',
                    ]);
                }
            });
        });
    }
}
