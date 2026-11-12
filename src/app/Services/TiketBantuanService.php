<?php

namespace App\Services;

use App\Models\TiketBantuan;
use App\Models\TiketBantuanBalasan;
use App\Models\TiketBantuanLampiran;
use App\Models\User;
use App\Notifications\TiketBalasanAdminNotification;
use App\Notifications\TiketBalasanMemberNotification;
use App\Notifications\TiketBaruNotification;
use App\Notifications\TiketStatusDiubahNotification;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TiketBantuanService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Generate nomor tiket berformat TKT-YYYYMM-XXXX secara atomik.
     */
    public function generateNomorTiket(): string
    {
        $prefix = 'TKT-' . now()->format('Ym') . '-';

        $latest = TiketBantuan::query()
            ->where('nomor', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $counter = 1;
        if ($latest) {
            $suffix = substr($latest->nomor, strlen($prefix));
            $counter = ((int) $suffix) + 1;
        }

        return $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Membuat tiket baru oleh member.
     *
     * @param array{subjek: string, pesan: string} $data
     * @param array<int, UploadedFile> $images
     */
    public function createTicket(User $user, array $data, array $images = []): TiketBantuan
    {
        return DB::transaction(function () use ($user, $data, $images) {
            $nomor = $this->generateNomorTiket();

            $tiket = TiketBantuan::create([
                'nomor'         => $nomor,
                'user_id'       => $user->id,
                'subjek'        => $data['subjek'],
                'status'        => TiketBantuan::STATUS_OPEN,
                'last_reply_at' => now(),
                'last_reply_by' => 'member',
            ]);

            $balasan = TiketBantuanBalasan::create([
                'tiket_id' => $tiket->id,
                'user_id'  => $user->id,
                'pengirim' => TiketBantuanBalasan::PENGIRIM_MEMBER,
                'pesan'    => $data['pesan'],
            ]);

            $this->storeAttachments($balasan, $tiket->id, $images);

            // Audit Log
            $this->auditLogService->record(
                kategori: 'tiket_bantuan',
                aksi: 'buat_tiket_bantuan',
                deskripsi: "Member {$user->name} membuat tiket bantuan [{$tiket->nomor}]: {$tiket->subjek}",
                subject: $tiket,
                perubahan: [
                    'nomor'    => $tiket->nomor,
                    'subjek'   => $tiket->subjek,
                    'lampiran' => count($images),
                ],
                user: $user
            );

            // Notifikasi ke seluruh Admin
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new TiketBaruNotification($tiket));
            }

            return $tiket;
        });
    }

    /**
     * Member membalas tiket.
     *
     * @param array<int, UploadedFile> $images
     */
    public function replyByMember(TiketBantuan $tiket, User $user, string $pesan, array $images = []): TiketBantuanBalasan
    {
        if ($tiket->user_id !== $user->id) {
            throw new DomainException('Anda tidak memiliki akses ke tiket ini.');
        }

        if (!$tiket->canReplyByMember()) {
            throw new DomainException("Tiket dengan status '{$tiket->status_label}' tidak dapat dibalas. Silakan buka kembali tiket terlebih dahulu.");
        }

        return DB::transaction(function () use ($tiket, $user, $pesan, $images) {
            $balasan = TiketBantuanBalasan::create([
                'tiket_id' => $tiket->id,
                'user_id'  => $user->id,
                'pengirim' => TiketBantuanBalasan::PENGIRIM_MEMBER,
                'pesan'    => $pesan,
            ]);

            $this->storeAttachments($balasan, $tiket->id, $images);

            $tiket->update([
                'status'        => TiketBantuan::STATUS_OPEN,
                'last_reply_at' => now(),
                'last_reply_by' => 'member',
            ]);

            // Audit Log
            $this->auditLogService->record(
                kategori: 'tiket_bantuan',
                aksi: 'balas_tiket_bantuan',
                deskripsi: "Member {$user->name} membalas tiket [{$tiket->nomor}]",
                subject: $tiket,
                perubahan: [
                    'balasan_id' => $balasan->id,
                    'lampiran'   => count($images),
                ],
                user: $user
            );

            // Notifikasi ke seluruh Admin
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new TiketBalasanMemberNotification($tiket, $balasan));
            }

            return $balasan;
        });
    }

    /**
     * Admin membalas tiket.
     *
     * @param array<int, UploadedFile> $images
     */
    public function replyByAdmin(TiketBantuan $tiket, User $admin, string $pesan, array $images = []): TiketBantuanBalasan
    {
        return DB::transaction(function () use ($tiket, $admin, $pesan, $images) {
            $balasan = TiketBantuanBalasan::create([
                'tiket_id' => $tiket->id,
                'user_id'  => $admin->id,
                'pengirim' => TiketBantuanBalasan::PENGIRIM_ADMIN,
                'pesan'    => $pesan,
            ]);

            $this->storeAttachments($balasan, $tiket->id, $images);

            $tiket->update([
                'status'        => TiketBantuan::STATUS_ANSWERED,
                'last_reply_at' => now(),
                'last_reply_by' => 'admin',
            ]);

            // Audit Log
            $this->auditLogService->record(
                kategori: 'tiket_bantuan',
                aksi: 'balas_tiket_bantuan',
                deskripsi: "Admin {$admin->name} membalas tiket [{$tiket->nomor}]",
                subject: $tiket,
                perubahan: [
                    'balasan_id' => $balasan->id,
                    'lampiran'   => count($images),
                ],
                user: $admin
            );

            // Notifikasi ke pemilik tiket
            $tiket->user?->notify(new TiketBalasanAdminNotification($tiket, $balasan));

            return $balasan;
        });
    }

    /**
     * Admin memperbarui status tiket secara manual.
     */
    public function updateStatusByAdmin(TiketBantuan $tiket, User $admin, string $newStatus, ?string $catatan = null): TiketBantuan
    {
        if (!array_key_exists($newStatus, TiketBantuan::STATUS_LABELS)) {
            throw new DomainException("Status '{$newStatus}' tidak valid.");
        }

        return DB::transaction(function () use ($tiket, $admin, $newStatus, $catatan) {
            $statusLama = $tiket->status;

            $updateData = [
                'status' => $newStatus,
            ];

            if ($newStatus === TiketBantuan::STATUS_RESOLVED) {
                $updateData['resolved_at'] = now();
            } elseif ($newStatus === TiketBantuan::STATUS_CLOSED) {
                $updateData['closed_at'] = now();
            }

            $tiket->update($updateData);

            // Audit Log
            $this->auditLogService->record(
                kategori: 'tiket_bantuan',
                aksi: 'ubah_status_tiket_bantuan',
                deskripsi: "Admin {$admin->name} mengubah status tiket [{$tiket->nomor}] dari '{$statusLama}' menjadi '{$newStatus}'",
                subject: $tiket,
                perubahan: [
                    'status_lama' => $statusLama,
                    'status_baru' => $newStatus,
                    'catatan'     => $catatan,
                ],
                user: $admin
            );

            // Notifikasi ke member
            $tiket->user?->notify(new TiketStatusDiubahNotification($tiket, $statusLama, $newStatus, $catatan));

            return $tiket;
        });
    }

    /**
     * Member menandai tiket telah selesai (resolved).
     */
    public function resolveByMember(TiketBantuan $tiket, User $user): TiketBantuan
    {
        if ($tiket->user_id !== $user->id) {
            throw new DomainException('Anda tidak memiliki akses ke tiket ini.');
        }

        if (!$tiket->canResolveByMember()) {
            throw new DomainException("Tiket tidak dapat ditandai selesai pada status saat ini.");
        }

        return DB::transaction(function () use ($tiket, $user) {
            $tiket->update([
                'status'      => TiketBantuan::STATUS_RESOLVED,
                'resolved_at' => now(),
            ]);

            $this->auditLogService->record(
                kategori: 'tiket_bantuan',
                aksi: 'selesaikan_tiket_bantuan',
                deskripsi: "Member {$user->name} menandai tiket [{$tiket->nomor}] selesai",
                subject: $tiket,
                user: $user
            );

            return $tiket;
        });
    }

    /**
     * Member membuka kembali tiket yang telah resolved.
     */
    public function reopenByMember(TiketBantuan $tiket, User $user): TiketBantuan
    {
        if ($tiket->user_id !== $user->id) {
            throw new DomainException('Anda tidak memiliki akses ke tiket ini.');
        }

        if (!$tiket->canReopenByMember()) {
            throw new DomainException("Hanya tiket berstatus 'Selesai' (Resolved) yang dapat dibuka kembali.");
        }

        return DB::transaction(function () use ($tiket, $user) {
            $tiket->update([
                'status'        => TiketBantuan::STATUS_OPEN,
                'resolved_at'   => null,
                'last_reply_at' => now(),
                'last_reply_by' => 'member',
            ]);

            $this->auditLogService->record(
                kategori: 'tiket_bantuan',
                aksi: 'buka_kembali_tiket_bantuan',
                deskripsi: "Member {$user->name} membuka kembali tiket [{$tiket->nomor}]",
                subject: $tiket,
                user: $user
            );

            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new TiketStatusDiubahNotification($tiket, TiketBantuan::STATUS_RESOLVED, TiketBantuan::STATUS_OPEN));
            }

            return $tiket;
        });
    }

    /**
     * Cron scheduler: Menutup tiket Resolved yang telah melewati N hari tanpa aktivitas.
     */
    public function closeResolvedTicketsAfterDays(int $days = 7): int
    {
        $cutoff = now()->subDays($days);

        $tickets = TiketBantuan::query()
            ->where('status', TiketBantuan::STATUS_RESOLVED)
            ->where('resolved_at', '<=', $cutoff)
            ->get();

        $count = 0;
        foreach ($tickets as $tiket) {
            $tiket->update([
                'status'    => TiketBantuan::STATUS_CLOSED,
                'closed_at' => now(),
            ]);

            $this->auditLogService->record(
                kategori: 'tiket_bantuan',
                aksi: 'tutup_tiket_bantuan',
                deskripsi: "Sistem menutup otomatis tiket [{$tiket->nomor}] setelah {$days} hari berstatus Resolved tanpa aktivitas",
                subject: $tiket
            );

            $count++;
        }

        return $count;
    }

    /**
     * Mengambil tiket dengan paginasi untuk Admin.
     *
     * @param array<string, mixed> $filters
     */
    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = TiketBantuan::query()
            ->with(['user', 'balasan' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('balasan');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('subjek', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Mengambil tiket dengan paginasi untuk Member.
     *
     * @param array<string, mixed> $filters
     */
    public function paginateForMember(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = TiketBantuan::query()
            ->where('user_id', $user->id)
            ->withCount('balasan');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('subjek', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'active') {
                $query->whereIn('status', [
                    TiketBantuan::STATUS_OPEN,
                    TiketBantuan::STATUS_IN_PROGRESS,
                    TiketBantuan::STATUS_ANSWERED,
                ]);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        return $query->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Mengambil detail lengkap tiket beserta percakapan & lampiran.
     */
    public function getTicketDetail(int|string $id, ?User $forUser = null): TiketBantuan
    {
        $query = TiketBantuan::query()
            ->with([
                'user:id,name,username,email,role,status',
                'balasan' => fn ($q) => $q->orderBy('created_at', 'asc')->with([
                    'user:id,name,username,role',
                    'lampiran',
                ]),
            ]);

        if ($forUser && !$forUser->isAdmin()) {
            $query->where('user_id', $forUser->id);
        }

        return $query->findOrFail($id);
    }

    /**
     * Menyimpan berkas lampiran ke disk privat (local).
     *
     * @param array<int, UploadedFile> $images
     */
    protected function storeAttachments(TiketBantuanBalasan $balasan, int $tiketId, array $images): void
    {
        foreach ($images as $image) {
            if (!$image instanceof UploadedFile) {
                continue;
            }

            $extension = $image->getClientOriginalExtension() ?: 'jpg';
            $fileName = $image->getClientOriginalName();
            $fileSize = $image->getSize();
            $mimeType = $image->getMimeType() ?: 'image/jpeg';

            // Master rule 5: disk local root is storage/app/private, do NOT prefix with private/
            $storedName = Str::uuid() . '.' . $extension;
            $path = $image->storeAs("tiket_bantuan/{$tiketId}", $storedName, 'local');

            TiketBantuanLampiran::create([
                'balasan_id' => $balasan->id,
                'file_path'  => $path,
                'file_name'  => $fileName,
                'file_size'  => $fileSize,
                'mime_type'  => $mimeType,
            ]);
        }
    }

    /**
     * Ekspor seluruh tiket bantuan ke format berkas XLSX.
     *
     * @param array<string, mixed> $filters
     */
    public function exportXlsx(array $filters = []): BinaryFileResponse
    {
        $columns = [
            ['label' => 'Nomor Tiket', 'width' => 18, 'type' => 'text'],
            ['label' => 'Tanggal Dibuat', 'width' => 20],
            ['label' => 'Subjek Kendala', 'width' => 35],
            ['label' => 'Nama Pelapor', 'width' => 25],
            ['label' => 'Username Pelapor', 'width' => 18],
            ['label' => 'Email Pelapor', 'width' => 25],
            ['label' => 'Status Tiket', 'width' => 20, 'align' => 'center'],
            ['label' => 'Jumlah Balasan', 'width' => 16, 'align' => 'center', 'type' => 'number'],
            ['label' => 'Balasan Terakhir Oleh', 'width' => 22],
            ['label' => 'Waktu Pembaruan Terakhir', 'width' => 20],
        ];

        $query = TiketBantuan::query()
            ->with(['user', 'balasan.user' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('balasan');

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('subjek', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $query->orderByDesc('last_reply_at')->orderByDesc('created_at');

        // Audit Log
        $actor = Auth::user();
        if ($actor) {
            $this->auditLogService->record(
                kategori: 'bantuan',
                aksi: 'ekspor_data',
                deskripsi: AuditLogService::formatActor($actor) . ' mengekspor data tiket bantuan ke format XLSX.',
                tingkat: 'info',
                user: $actor
            );
        }

        $xlsxService = app(XlsxExportService::class);

        return $xlsxService->download('tiket_bantuan_yzd', 'Tiket Bantuan', $columns, function ($addRow) use ($query): void {
            $query->chunk(500, function ($tickets) use ($addRow): void {
                foreach ($tickets as $tiket) {
                    $statusLabel = TiketBantuan::STATUS_LABELS[$tiket->status] ?? ucfirst($tiket->status);
                    $lastReply = $tiket->balasan->first();
                    $lastReplyBy = $lastReply?->user?->name ?? '-';

                    $addRow([
                        (string) $tiket->nomor,
                        $tiket->created_at ? $tiket->created_at->format('Y-m-d H:i') : '-',
                        $tiket->subjek,
                        $tiket->user?->name ?? '-',
                        $tiket->user?->username ? '@' . $tiket->user->username : '-',
                        $tiket->user?->email ?? '-',
                        $statusLabel,
                        $tiket->balasan_count ?? 0,
                        $lastReplyBy,
                        $tiket->updated_at ? $tiket->updated_at->format('Y-m-d H:i') : '-',
                    ]);
                }
            });
        });
    }
}
