<?php

namespace App\Services;

use App\Models\KlaimReferral;
use App\Models\RelasiReferral;
use App\Models\User;
use App\Notifications\NewDownlineRegisteredNotification;
use App\Notifications\ReferralClaimStatusChangedNotification;
use App\Notifications\ReferralClaimSubmittedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReferralService
{
    /**
     * Generate kode referral unik untuk affiliator baru.
     * Format: 3 huruf nama (dibersihkan dan kapital) + 3 karakter alfanumerik kapital (total 6 karakter).
     * Contoh: ABI8K2, ABIX79.
     */
    public function generateReferralCode(string $name): string
    {
        $cleanName = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
        $prefix = str_pad(substr($cleanName, 0, 3), 3, 'X');
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $charsLen = strlen($chars);

        do {
            $randomSuffix = '';
            for ($i = 0; $i < 3; $i++) {
                $randomSuffix .= $chars[random_int(0, $charsLen - 1)];
            }
            $code = $prefix . $randomSuffix;
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Validasi kode referral milik affiliator aktif.
     */
    public function validateReferralCode(?string $code): ?User
    {
        if (empty($code)) {
            return null;
        }

        $cleanCode = strtoupper(trim($code));

        return User::query()
            ->where('referral_code', $cleanCode)
            ->where('role', 'affiliator')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Hubungkan investor (downline) ke affiliator (upline).
     */
    public function attachDownline(User $affiliator, User $downline, string $sumber = 'link_otomatis'): RelasiReferral
    {
        return DB::transaction(function () use ($affiliator, $downline, $sumber): RelasiReferral {
            if ($affiliator->id === $downline->id) {
                throw ValidationException::withMessages([
                    'referral_code' => 'Pengguna tidak dapat menghubungkan diri sendiri sebagai upline.',
                ]);
            }

            if ($downline->hasUpline()) {
                throw ValidationException::withMessages([
                    'referral_code' => 'Investor ini sudah terikat dengan mitra affiliator lain.',
                ]);
            }

            $relasi = RelasiReferral::create([
                'affiliator_id' => $affiliator->id,
                'downline_id'   => $downline->id,
                'sumber'        => $sumber,
                'joined_at'     => now(),
                'status'        => 'active',
            ]);

            if ($sumber === 'link_otomatis') {
                $affiliator->notify(new NewDownlineRegisteredNotification($downline, $affiliator));
            }

            return $relasi;
        });
    }

    /**
     * Ambil daftar investor yang memenuhi syarat untuk diklaim susulan oleh Affiliator.
     * Syarat:
     * 1. Peran investor (bukan admin atau diri sendiri).
     * 2. Belum memiliki upline di relasi_referral.
     * 3. Tidak sedang memiliki klaim referral berstatus 'pending'.
     *
     * @return Collection<int, User>
     */
    public function getEligibleDownlinesForClaim(User $affiliator): Collection
    {
        return User::query()
            ->where('role', 'investor')
            ->where('id', '!=', $affiliator->id)
            ->whereDoesntHave('relasiUpline')
            ->whereDoesntHave('klaimReferralSebagaiDownline', function ($q) {
                $q->where('status', 'pending');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'email', 'phone', 'created_at']);
    }

    /**
     * Periksa ketersediaan dan keabsahan calon downline berdasarkan username untuk klaim susulan.
     *
     * @return array{valid: bool, message: string, downline?: array<string, mixed>}
     */
    public function checkDownlineForClaim(User $affiliator, string $username): array
    {
        $cleanUsername = strtolower(ltrim(trim($username), '@'));

        $user = User::query()
            ->whereRaw('LOWER(username) = ?', [$cleanUsername])
            ->first();

        if (!$user) {
            return [
                'valid'   => false,
                'message' => "Investor dengan username '{$username}' tidak ditemukan dalam sistem.",
            ];
        }

        if ($user->id === $affiliator->id) {
            return [
                'valid'   => false,
                'message' => 'Anda tidak dapat mengklaim akun Anda sendiri.',
            ];
        }

        if ($user->role !== 'investor') {
            return [
                'valid'   => false,
                'message' => 'Akun tersebut bukan akun Investor yang dapat diklaim sebagai downline.',
            ];
        }

        $downline = $user;

        if ($downline->hasUpline()) {
            return [
                'valid'   => false,
                'message' => 'Investor ini sudah terdaftar di bawah mitra affiliator lain.',
            ];
        }

        $hasPendingClaim = KlaimReferral::query()
            ->where('downline_id', $downline->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingClaim) {
            return [
                'valid'   => false,
                'message' => 'Permohonan klaim untuk investor ini sedang dalam proses peninjauan Administrator.',
            ];
        }

        return [
            'valid'    => true,
            'message'  => 'Investor valid dan dapat diajukan untuk klaim susulan.',
            'downline' => [
                'id'               => $downline->id,
                'name'             => $downline->name,
                'username'         => $downline->username,
                'created_at_label' => tanggal_singkat($downline->created_at),
            ],
        ];
    }

    /**
     * Ajukan klaim susulan relasi referral (PB 4).
     *
     * @throws ValidationException
     */
    public function submitClaim(User $affiliator, int $downlineId, ?UploadedFile $buktiChat, string $alasan): KlaimReferral
    {
        return DB::transaction(function () use ($affiliator, $downlineId, $buktiChat, $alasan): KlaimReferral {
            $downline = User::query()
                ->where('role', 'investor')
                ->where('id', $downlineId)
                ->lockForUpdate()
                ->first();

            if (!$downline) {
                throw ValidationException::withMessages([
                    'downline_id' => 'Data calon downline tidak ditemukan dalam sistem.',
                ]);
            }

            if ($downline->id === $affiliator->id) {
                throw ValidationException::withMessages([
                    'downline_id' => 'Anda tidak dapat mengklaim akun Anda sendiri.',
                ]);
            }

            if ($downline->hasUpline()) {
                throw ValidationException::withMessages([
                    'downline_id' => 'Investor ini telah terdaftar resmi di bawah mitra affiliator lain.',
                ]);
            }

            $hasPendingClaim = KlaimReferral::query()
                ->where('downline_id', $downline->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingClaim) {
                throw ValidationException::withMessages([
                    'downline_id' => 'Permohonan klaim untuk investor ini sedang dalam proses peninjauan oleh Administrator.',
                ]);
            }

            // Simpan berkas bukti ke private storage jika dilampirkan
            $path = null;
            if ($buktiChat) {
                $directory = "referral_claims/{$affiliator->id}";
                $filename = 'bukti_' . time() . '_' . Str::random(8) . '.' . $buktiChat->getClientOriginalExtension();
                $path = $buktiChat->storeAs($directory, $filename, 'local');
            }

            $claim = KlaimReferral::create([
                'affiliator_id'   => $affiliator->id,
                'downline_id'     => $downline->id,
                'bukti_chat_path' => $path,
                'alasan'          => trim($alasan),
                'status'          => 'pending',
            ]);

            // Kirim notifikasi in-app ke seluruh administrator
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ReferralClaimSubmittedNotification($claim->fresh(['affiliator', 'downline'])));
            }

            return $claim;
        });
    }

    /**
     * Administrator meninjau permohonan klaim referral susulan.
     *
     * @throws ValidationException
     */
    public function reviewClaim(KlaimReferral $claim, string $status, ?string $catatanAdmin, User $admin): KlaimReferral
    {
        return DB::transaction(function () use ($claim, $status, $catatanAdmin, $admin): KlaimReferral {
            /** @var KlaimReferral $lockedClaim */
            $lockedClaim = KlaimReferral::where('id', $claim->id)->lockForUpdate()->firstOrFail();

            if ($lockedClaim->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Permohonan klaim ini sudah pernah diproses sebelumnya.',
                ]);
            }

            if ($status === 'approved') {
                // Pastikan downline belum memiliki upline
                if ($lockedClaim->downline->hasUpline()) {
                    throw ValidationException::withMessages([
                        'status' => 'Gagal menyetujui. Investor ini ternyata sudah terhubung dengan affiliator lain.',
                    ]);
                }

                // Buat relasi referral resmi
                RelasiReferral::create([
                    'affiliator_id' => $lockedClaim->affiliator_id,
                    'downline_id'   => $lockedClaim->downline_id,
                    'sumber'        => 'klaim_manual',
                    'joined_at'     => now(),
                    'status'        => 'active',
                ]);

                // Batalkan atau tolak klaim pending lain untuk downline yang sama jika ada
                $otherPendingClaims = KlaimReferral::where('downline_id', $lockedClaim->downline_id)
                    ->where('id', '!=', $lockedClaim->id)
                    ->where('status', 'pending')
                    ->with(['affiliator', 'downline'])
                    ->get();

                foreach ($otherPendingClaims as $otherClaim) {
                    $otherClaim->update([
                        'status'        => 'rejected',
                        'catatan_admin' => 'Dibatalkan otomatis karena investor telah disetujui untuk affiliator lain.',
                        'reviewed_by'   => $admin->id,
                        'reviewed_at'   => now(),
                    ]);

                    $otherClaim->affiliator?->notify(
                        new ReferralClaimStatusChangedNotification(
                            $otherClaim,
                            'rejected',
                            'Dibatalkan otomatis karena investor telah disetujui untuk affiliator lain.'
                        )
                    );
                }

                $lockedClaim->update([
                    'status'        => 'approved',
                    'catatan_admin' => $catatanAdmin,
                    'reviewed_by'   => $admin->id,
                    'reviewed_at'   => now(),
                ]);

                // Kirim notifikasi in-app ke affiliator bahwa klaim disetujui
                $lockedClaim->affiliator?->notify(
                    new ReferralClaimStatusChangedNotification($lockedClaim, 'approved', $catatanAdmin)
                );
            } else {
                if (empty(trim((string) $catatanAdmin))) {
                    throw ValidationException::withMessages([
                        'catatan_admin' => 'Catatan alasan penolakan wajib diisi untuk memberi penjelasan kepada affiliator.',
                    ]);
                }

                $lockedClaim->update([
                    'status'        => 'rejected',
                    'catatan_admin' => trim($catatanAdmin),
                    'reviewed_by'   => $admin->id,
                    'reviewed_at'   => now(),
                ]);

                // Kirim notifikasi in-app ke affiliator bahwa klaim ditolak
                $lockedClaim->affiliator?->notify(
                    new ReferralClaimStatusChangedNotification($lockedClaim, 'rejected', $catatanAdmin)
                );
            }

            return $lockedClaim->fresh(['affiliator', 'downline', 'reviewer']);
        });
    }

    /**
     * Ambil statistik ringkas jaringan affiliator.
     *
     * @return array<string, int|string>
     */
    public function getAffiliatorNetworkStats(User $affiliator): array
    {
        $totalDownlines = RelasiReferral::where('affiliator_id', $affiliator->id)->count();

        $activeDownlines = RelasiReferral::where('affiliator_id', $affiliator->id)
            ->whereHas('downline', function ($q) {
                $q->where('status', 'active');
            })
            ->count();

        $pendingClaims = KlaimReferral::where('affiliator_id', $affiliator->id)
            ->where('status', 'pending')
            ->count();

        return [
            'referral_code'    => $affiliator->referral_code ?? '-',
            'total_downlines'  => $totalDownlines,
            'active_downlines' => $activeDownlines,
            'pending_claims'   => $pendingClaims,
        ];
    }

    /**
     * Ambil daftar downline terpaginasi milik Affiliator.
     *
     * @param array<string, mixed> $filters
     */
    public function getPaginatedDownlines(User $affiliator, array $filters): LengthAwarePaginator
    {
        $query = RelasiReferral::query()
            ->with(['downline.akunCopytrades'])
            ->where('affiliator_id', $affiliator->id);

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->whereHas('downline', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['sumber'])) {
            $query->where('sumber', $filters['sumber']);
        }

        if (!empty($filters['status'])) {
            $status = $filters['status'];
            $query->whereHas('downline', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $allowedSorts = ['joined_at', 'status'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts, true) ? $filters['sort_by'] : 'joined_at';
        $sortDirection = strtolower($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 10)));

        return $query->paginate($perPage)
            ->withQueryString()
            ->through(function (RelasiReferral $rel): array {
                $downline = $rel->downline;
                $akun = $downline?->akunCopytrades?->first();

                return [
                    'id'               => $rel->id,
                    'name'             => $downline?->name ?? '-',
                    'username'         => $downline?->username ?? '-',
                    'email'            => $downline?->email ?? '-',
                    'phone'            => $downline?->phone ?? '-',
                    'user_status'      => $downline?->status ?? 'pending',
                    'copytrade_status' => $akun?->status ?? 'pending',
                    'tier'             => $akun?->tier ?? null,
                    'sumber'           => $rel->sumber,
                    'sumber_label'     => $rel->sumber === 'link_otomatis' ? 'Kode Registrasi' : 'Klaim Susulan',
                    'joined_at'        => $rel->joined_at ? $rel->joined_at->toIso8601String() : null,
                    'joined_at_label'  => tanggal_singkat($rel->joined_at),
                ];
            });
    }

    /**
     * Ambil daftar klaim referral susulan terpaginasi.
     *
     * @param array<string, mixed> $filters
     */
    public function getPaginatedClaims(array $filters, ?User $affiliator = null, string $pageName = 'page'): LengthAwarePaginator
    {
        $query = KlaimReferral::query()
            ->with(['affiliator', 'downline', 'reviewer']);

        if ($affiliator) {
            $query->where('affiliator_id', $affiliator->id);
        }

        $search = trim((string) ($filters['search'] ?? $filters['claim_search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('affiliator', fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"))
                    ->orWhereHas('downline', fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
            });
        }

        $status = $filters['status'] ?? $filters['claim_status'] ?? null;
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $allowedSorts = ['created_at', 'status'];
        $rawSort = $filters['sort_by'] ?? $filters['claim_sort_by'] ?? '';
        $sortBy = in_array($rawSort, $allowedSorts, true) ? $rawSort : 'created_at';
        $sortDirection = strtolower($filters['sort_direction'] ?? $filters['claim_sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? $filters['claim_per_page'] ?? 10)));

        return $query->paginate($perPage, ['*'], $pageName)
            ->withQueryString()
            ->through(function (KlaimReferral $claim): array {
                return [
                    'id'         => $claim->id,
                    'affiliator' => [
                        'id'            => $claim->affiliator?->id,
                        'name'          => $claim->affiliator?->name ?? '-',
                        'username'      => $claim->affiliator?->username ?? '-',
                        'referral_code' => $claim->affiliator?->referral_code ?? '-',
                    ],
                    'downline' => [
                        'id'         => $claim->downline?->id,
                        'name'       => $claim->downline?->name ?? '-',
                        'username'   => $claim->downline?->username ?? '-',
                        'email'      => $claim->downline?->email ?? '-',
                        'created_at' => $claim->downline?->created_at ? tanggal_singkat($claim->downline->created_at) : '-',
                    ],
                    'alasan'         => $claim->alasan,
                    'bukti_chat_url' => $claim->bukti_chat_path ? route('documents.claim_proof', ['id' => $claim->id]) : null,
                    'bukti_is_pdf'   => $claim->bukti_chat_path ? str_ends_with(strtolower($claim->bukti_chat_path), '.pdf') : false,
                    'status'         => $claim->status,
                    'status_label'   => match ($claim->status) {
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default    => 'Pending',
                    },
                    'catatan_admin'    => $claim->catatan_admin,
                    'reviewed_by_name' => $claim->reviewer?->name,
                    'reviewed_at'      => $claim->reviewed_at ? tanggal_waktu_indo($claim->reviewed_at) : null,
                    'created_at'       => $claim->created_at ? $claim->created_at->toIso8601String() : null,
                    'created_at_label' => tanggal_waktu_indo($claim->created_at),
                    'created_at_date'  => tanggal_singkat($claim->created_at),
                    'created_at_time'  => $claim->created_at ? $claim->created_at->format('H:i \W\I\B') : '',
                ];
            });
    }

    /**
     * Ambil rekap seluruh mitra affiliator untuk panel Admin.
     *
     * @param array<string, mixed> $filters
     */
    public function getAllAffiliatesSummary(array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->where('role', 'affiliator')
            ->withCount([
                'relasiDownlines as total_downlines',
                'relasiDownlines as active_downlines' => function ($q) {
                    $q->whereHas('downline', fn ($sub) => $sub->where('status', 'active'));
                },
            ]);

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('referral_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('total_downlines');

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 10)));

        return $query->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user): array {
                return [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'username'         => $user->username,
                    'email'            => $user->email,
                    'phone'            => $user->phone,
                    'referral_code'    => $user->referral_code ?? '-',
                    'is_active'        => $user->is_active,
                    'status'           => $user->status,
                    'total_downlines'  => $user->total_downlines ?? 0,
                    'active_downlines' => $user->active_downlines ?? 0,
                    'joined_at_label'  => tanggal_singkat($user->created_at),
                ];
            });
    }
}
