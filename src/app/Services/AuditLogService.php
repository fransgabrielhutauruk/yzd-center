<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditLogService
{
    /**
     * Daftar kunci sensitif yang wajib disensor secara otomatis.
     *
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'master_password',
        'investor_password',
        'vps_password',
        'remember_token',
        'token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'app_key',
        'secret',
    ];

    /**
     * Label representatif untuk setiap kategori modul aktivitas.
     *
     * @var array<string, string>
     */
    public const KATEGORI_LABELS = [
        'autentikasi'        => 'Autentikasi',
        'kyc_dokumen'        => 'Dokumen',
        'copytrade'          => 'Kelola Copytrade',
        'master_fee'         => 'Master Fee',
        'komisi_afiliasi'    => 'Kemitraan & Komisi',
        'pengguna'           => 'Manajemen Pengguna',
        'undangan'           => 'Tautan Undangan',
        'edukasi_pengaturan' => 'Edukasi & Legal',
        'profit_master'      => 'Materi Promosi',
        'tiket_bantuan'      => 'Tiket Bantuan',
    ];

    /**
     * Label representatif untuk setiap aksi.
     *
     * @var array<string, string>
     */
    public const AKSI_LABELS = [
        'login'                      => 'Masuk (Login)',
        'login_gagal'                => 'Percobaan Login Gagal',
        'logout'                     => 'Keluar (Logout)',
        'register'                   => 'Registrasi Akun Baru',
        'ganti_password'             => 'Ganti Kata Sandi',
        'unggah_ulang_kyc'           => 'Unggah Ulang Dokumen KYC',
        'verifikasi_kyc'             => 'Verifikasi Dokumen KYC',
        'lihat_dokumen'              => 'Akses Berkas Privat',
        'ajukan_copytrade'           => 'Pengajuan Akun Copytrade',
        'verifikasi_copytrade'       => 'Verifikasi Akun Copytrade',
        'lihat_kredensial_mt4'       => 'Akses Kredensial MT4/VPS',
        'catat_saldo'                => 'Pencatatan Saldo Bulanan',
        'ubah_saldo'                 => 'Pembaruan Saldo Bulanan',
        'hapus_saldo'                => 'Penghapusan Catatan Saldo',
        'ajukan_klaim_referral'      => 'Pengajuan Klaim Referral',
        'verifikasi_klaim_referral'  => 'Verifikasi Klaim Referral',
        'ajukan_klaim_komisi'        => 'Pengajuan Pencairan Komisi',
        'proses_klaim_komisi'        => 'Pencairan Komisi Bank',
        'ubah_status_user'           => 'Ubah Status Akun',
        'ubah_peran_user'            => 'Alih Peran Pengguna',
        'buat_undangan'              => 'Penerbitan Tautan Undangan',
        'hapus_undangan'             => 'Penghapusan Tautan Undangan',
        'kelola_edukasi'             => 'Pembaruan Materi Bantuan',
        'ubah_konten_legal'          => 'Pembaruan Dokumen Legal',
        'ekspor_audit_log'           => 'Ekspor Log Aktivitas',
        'buat_profit_master'         => 'Penerbitan Materi Promosi',
        'ubah_profit_master'         => 'Pembaruan Materi Promosi',
        'hapus_profit_master'        => 'Penghapusan Materi Promosi',
        'buat_tiket_bantuan'         => 'Pembuatan Tiket Bantuan',
        'balas_tiket_bantuan'        => 'Balasan Tiket Bantuan',
        'ubah_status_tiket_bantuan'  => 'Perubahan Status Tiket Bantuan',
        'selesaikan_tiket_bantuan'   => 'Penyelesaian Tiket Bantuan',
        'buka_kembali_tiket_bantuan' => 'Pembukaan Kembali Tiket Bantuan',
        'tutup_tiket_bantuan'        => 'Penutupan Tiket Bantuan',
        'pulihkan_data'              => 'Pemulihan Data Terhapus',
        'bersihkan_data'             => 'Pembersihan Data Permanen (Purge)',
        'ekspor_data'                => 'Ekspor Data XLSX',
    ];

    /**
     * Format label representatif aktor/pelaku audit.
     * Mencegah duplikasi kata jika nama pengguna sudah mengandung kata 'Admin' (contoh: 'Administrator').
     */
    public static function formatActor(?User $user): string
    {
        if (!$user) {
            return 'Sistem';
        }

        $name = trim($user->name ?? '');
        $title = preg_match('/admin/i', $name) ? $name : "Administrator {$name}";

        return $title;
    }

    /**
     * Format label representatif subjek pengguna.
     * Memprioritaskan username (@username) agar mudah dibaca oleh admin tanpa perlu merujuk ID database.
     */
    public static function formatTargetUser(?User $user, ?int $fallbackId = null): string
    {
        if (!$user) {
            return $fallbackId ? "pengguna #{$fallbackId}" : 'pengguna';
        }

        if (!empty($user->username)) {
            return "@{$user->username}";
        }

        if (!empty($user->name)) {
            return $user->name;
        }

        return $fallbackId ? "pengguna #{$fallbackId}" : 'pengguna';
    }

    /**
     * Mencatat jejak audit log aktivitas secara aman dan tersanitasi.
     *
     * @param array<string, mixed>|null $perubahan
     */
    public function record(
        string $kategori,
        string $aksi,
        string $deskripsi,
        ?Model $subject = null,
        ?array $perubahan = null,
        string $tingkat = 'info',
        ?User $user = null,
        bool $withCooldown = false
    ): ?LogAktivitas {
        $actor = $user ?? Auth::user();

        $userId = $actor?->id;
        $subjectType = $subject ? $subject->getMorphClass() : null;
        $subjectId = $subject?->getKey();

        // Anti-Spam: Cek apakah aksi yang sama pada objek yang sama telah tercatat dalam 5 menit terakhir
        if ($withCooldown && $userId) {
            $recentLog = LogAktivitas::where('user_id', $userId)
                ->where('aksi', $aksi)
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                ->first();

            if ($recentLog) {
                return $recentLog;
            }
        }

        // Sanitasi otomatis seluruh field sensitif (password, master password, token, dll.)
        $sanitizedPerubahan = $perubahan !== null ? $this->maskSensitiveData($perubahan) : null;

        $ipAddress = request()?->ip();
        $userAgent = request()?->userAgent();

        // Nama dan username snapshot pelaku
        $userName = $actor?->name ?? ($perubahan['meta']['name'] ?? ($perubahan['meta']['username'] ?? 'Tamu / Anonim'));
        $userUsername = $actor?->username ?? ($perubahan['meta']['username'] ?? null);
        $userRole = $actor?->role ?? 'guest';

        return LogAktivitas::create([
            'user_id'       => $userId,
            'user_name'     => $userName,
            'user_username' => $userUsername,
            'user_role'     => $userRole,
            'kategori'      => $kategori,
            'aksi'          => $aksi,
            'tingkat'       => in_array($tingkat, ['info', 'warning', 'critical'], true) ? $tingkat : 'info',
            'deskripsi'     => $deskripsi,
            'subject_type'  => $subjectType,
            'subject_id'    => $subjectId,
            'perubahan'     => $sanitizedPerubahan,
            'ip_address'    => $ipAddress,
            'user_agent'    => $userAgent,
            'created_at'    => Carbon::now(),
        ]);
    }

    /**
     * Mengambil log aktivitas terpaginasi dengan filter pencarian server-side.
     *
     * @param array<string, mixed> $filters
     */
    public function getPaginatedLogs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery($filters);

        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->getCollection()->transform(function (LogAktivitas $log) {
            return $this->formatLogItem($log);
        });

        return $paginator;
    }

    /**
     * Menghitung statistik KPI halaman Log Aktivitas.
     *
     * @return array<string, int>
     */
    public function getAuditStats(): array
    {
        return [
            'total_logs'      => LogAktivitas::count(),
            'today_logs'      => LogAktivitas::whereDate('created_at', Carbon::today())->count(),
            'critical_logs'   => LogAktivitas::where('tingkat', 'critical')->count(),
            'security_alerts' => LogAktivitas::where(function (Builder $q): void {
                $q->where('tingkat', 'warning')
                    ->orWhere('aksi', 'login_gagal');
            })->count(),
        ];
    }

    /**
     * Mengekspor audit log sesuai filter aktif ke format berkas Excel (.xlsx).
     *
     * @param array<string, mixed> $filters
     */
    public function exportXlsx(array $filters = []): BinaryFileResponse
    {
        $columns = [
            ['label' => 'ID', 'width' => 8, 'align' => 'center'],
            ['label' => 'Tanggal & Waktu', 'width' => 22],
            ['label' => 'Nama Pelaku', 'width' => 24],
            ['label' => 'Username', 'width' => 18],
            ['label' => 'Peran (Role)', 'width' => 16, 'align' => 'center'],
            ['label' => 'Kategori Modul', 'width' => 22],
            ['label' => 'Aksi', 'width' => 26],
            ['label' => 'Tingkat Risiko', 'width' => 16, 'align' => 'center'],
            ['label' => 'Deskripsi Aktivitas', 'width' => 48],
            ['label' => 'IP Address', 'width' => 18],
            ['label' => 'Perangkat & Browser', 'width' => 30],
            ['label' => 'Objek Target', 'width' => 22],
            ['label' => 'Rincian Perubahan (JSON)', 'width' => 65, 'type' => 'monospace'],
        ];

        $query = $this->buildFilteredQuery($filters);

        if (!empty($filters['sort_by'])) {
            $direction = strtolower($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
            $query->reorder($filters['sort_by'], $direction);
        }

        $xlsxService = app(XlsxExportService::class);

        return $xlsxService->download('audit_log_yzd', 'Audit Log', $columns, function ($addRow) use ($query): void {
            $query->chunk(500, function ($logs) use ($addRow): void {
                foreach ($logs as $log) {
                    $subjectText = $log->subject_type
                        ? class_basename($log->subject_type) . ' #' . $log->subject_id
                        : '-';

                    $perubahanJson = $log->perubahan
                        ? json_encode($log->perubahan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : '-';

                    $addRow([
                        (string) $log->id,
                        $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
                        $log->user_name ?? '-',
                        $log->user_username ? '@' . $log->user_username : '-',
                        strtoupper($log->user_role ?? 'GUEST'),
                        self::KATEGORI_LABELS[$log->kategori] ?? ucfirst($log->kategori),
                        self::AKSI_LABELS[$log->aksi] ?? ucfirst(str_replace('_', ' ', $log->aksi)),
                        strtoupper($log->tingkat),
                        $log->deskripsi,
                        $log->ip_address ?? '-',
                        $this->parseDeviceFromUserAgent($log->user_agent),
                        $subjectText,
                        $perubahanJson,
                    ]);
                }
            });
        });
    }

    /**
     * Membangun query Eloquent dengan filter yang diterapkan.
     *
     * @param array<string, mixed> $filters
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        $query = LogAktivitas::query()->orderBy('created_at', 'desc')->orderBy('id', 'desc');

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('deskripsi', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('user_username', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['kategori'])) {
            $query->where('kategori', $filters['kategori']);
        }

        if (!empty($filters['user_role'])) {
            $role = $filters['user_role'];
            if ($role === 'admin' || $role === 'administrator') {
                $query->whereIn('user_role', ['admin', 'administrator']);
            } else {
                $query->where('user_role', $role);
            }
        }

        if (!empty($filters['tingkat'])) {
            $query->where('tingkat', $filters['tingkat']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        return $query;
    }

    /**
     * Memformat objek log untuk konsumsi frontend Vue.
     *
     * @return array<string, mixed>
     */
    public function formatLogItem(LogAktivitas $log): array
    {
        return [
            'id'                => $log->id,
            'user_id'           => $log->user_id,
            'user_name'         => $log->user_name,
            'user_username'     => $log->user_username,
            'user_role'         => $log->user_role,
            'kategori'          => $log->kategori,
            'kategori_label'    => self::KATEGORI_LABELS[$log->kategori] ?? ucfirst($log->kategori),
            'aksi'              => $log->aksi,
            'aksi_label'        => self::AKSI_LABELS[$log->aksi] ?? ucfirst(str_replace('_', ' ', $log->aksi)),
            'tingkat'           => $log->tingkat,
            'deskripsi'         => $log->deskripsi,
            'subject_type'      => $log->subject_type ? class_basename($log->subject_type) : null,
            'subject_id'        => $log->subject_id,
            'perubahan'         => $log->perubahan,
            'ip_address'        => $log->ip_address,
            'user_agent'        => $log->user_agent,
            'perangkat_ringkas' => $this->parseDeviceFromUserAgent($log->user_agent),
            'created_at'        => $log->created_at?->toIso8601String(),
            'waktu_relatif'     => $log->created_at?->diffForHumans(),
            'waktu_lengkap'     => $log->created_at?->translatedFormat('d M Y, H:i:s'),
        ];
    }

    /**
     * Menyensor seluruh data sensitif dalam array secara rekursif.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function maskSensitiveData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            $isSensitive = in_array($lowerKey, self::SENSITIVE_KEYS, true)
                || str_contains($lowerKey, 'password')
                || str_contains($lowerKey, 'secret')
                || str_contains($lowerKey, 'token')
                || str_contains($lowerKey, 'pin');

            if ($isSensitive) {
                $sanitized[$key] = '[TERENKRIPSI/RAHASIA]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->maskSensitiveData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Mengurai string User-Agent menjadi label perangkat dan peramban ringkas.
     */
    public function parseDeviceFromUserAgent(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Perangkat Tidak Dikenal';
        }

        // Deteksi Sistem Operasi
        $os = 'Lainnya';
        if (str_contains($userAgent, 'Windows NT 10.0') || str_contains($userAgent, 'Windows NT 11.0')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Windows NT')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'iPhone')) {
            $os = 'iOS (iPhone)';
        } elseif (str_contains($userAgent, 'iPad')) {
            $os = 'iPadOS';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS X')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        }

        // Deteksi Peramban
        $browser = 'Browser Web';
        if (str_contains($userAgent, 'Edg/')) {
            $browser = 'Edge';
        } elseif (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera')) {
            $browser = 'Opera';
        } elseif (str_contains($userAgent, 'Chrome/')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Safari/') && !str_contains($userAgent, 'Chrome/')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Firefox/')) {
            $browser = 'Firefox';
        }

        return "{$browser} • {$os}";
    }
}
