<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

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
        'komisi_afiliasi'    => 'Kemitraan & Komisi',
        'pengguna'           => 'Manajemen Pengguna',
        'undangan'           => 'Tautan Undangan',
        'edukasi_pengaturan' => 'Edukasi & Legal',
        'profit_master'      => 'Materi Promosi',
    ];

    /**
     * Label representatif untuk setiap aksi.
     *
     * @var array<string, string>
     */
    public const AKSI_LABELS = [
        'login'                     => 'Masuk (Login)',
        'login_gagal'               => 'Percobaan Login Gagal',
        'logout'                    => 'Keluar (Logout)',
        'register'                  => 'Registrasi Akun Baru',
        'ganti_password'            => 'Ganti Kata Sandi',
        'unggah_ulang_kyc'          => 'Unggah Ulang Dokumen KYC',
        'verifikasi_kyc'            => 'Verifikasi Dokumen KYC',
        'lihat_dokumen'             => 'Akses Berkas Privat',
        'ajukan_copytrade'          => 'Pengajuan Akun Copytrade',
        'verifikasi_copytrade'      => 'Verifikasi Akun Copytrade',
        'lihat_kredensial_mt4'      => 'Akses Kredensial MT4/VPS',
        'catat_saldo'               => 'Pencatatan Saldo Bulanan',
        'ubah_saldo'                => 'Pembaruan Saldo Bulanan',
        'hapus_saldo'               => 'Penghapusan Catatan Saldo',
        'ajukan_klaim_referral'     => 'Pengajuan Klaim Referral',
        'verifikasi_klaim_referral' => 'Verifikasi Klaim Referral',
        'ajukan_klaim_komisi'       => 'Pengajuan Pencairan Komisi',
        'proses_klaim_komisi'       => 'Pencairan Komisi Bank',
        'ubah_status_user'          => 'Ubah Status Akun',
        'ubah_peran_user'           => 'Alih Peran Pengguna',
        'buat_undangan'             => 'Penerbitan Tautan Undangan',
        'hapus_undangan'            => 'Penghapusan Tautan Undangan',
        'kelola_edukasi'            => 'Pembaruan Materi Bantuan',
        'ubah_konten_legal'         => 'Pembaruan Dokumen Legal',
        'ekspor_audit_log'          => 'Ekspor Log Aktivitas',
        'buat_profit_master'        => 'Penerbitan Materi Promosi',
        'ubah_profit_master'        => 'Pembaruan Materi Promosi',
        'hapus_profit_master'       => 'Penghapusan Materi Promosi',
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
        $filename = 'audit_log_yzd_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'audit_xlsx_');

        $zip = new ZipArchive;
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat berkas arsip XLSX.');
        }

        // 1. [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>');

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>');

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>');

        // 4. xl/workbook.xml
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets>' .
            '<sheet name="Audit Log" sheetId="1" r:id="rId1"/>' .
            '</sheets>' .
            '</workbook>');

        // 5. xl/styles.xml
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="3">' .
            '<font><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>' .
            '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' .
            '<font><sz val="9"/><color rgb="FF334155"/><name val="Consolas"/></font>' .
            '</fonts>' .
            '<fills count="4">' .
            '<fill><patternFill patternType="none"/></fill>' .
            '<fill><patternFill patternType="gray125"/></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>' .
            '</fills>' .
            '<borders count="2">' .
            '<border><left/><right/><top/><bottom/></border>' .
            '<border>' .
            '<left style="thin"><color rgb="FFE2E8F0"/></left>' .
            '<right style="thin"><color rgb="FFE2E8F0"/></right>' .
            '<top style="thin"><color rgb="FFE2E8F0"/></top>' .
            '<bottom style="thin"><color rgb="FFE2E8F0"/></bottom>' .
            '</border>' .
            '</borders>' .
            '<cellStyleXfs count="1">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' .
            '</cellStyleXfs>' .
            '<cellXfs count="8">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' . // 0: Default
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>' . // 1: Header
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>' . // 2: Regular wrapped
            '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>' . // 3: Zebra wrapped
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="top"/></xf>' . // 4: Center Regular
            '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="top"/></xf>' . // 5: Center Zebra
            '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>' . // 6: Monospace JSON Regular
            '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>' . // 7: Monospace JSON Zebra
            '</cellXfs>' .
            '</styleSheet>');

        // 6. xl/worksheets/sheet1.xml
        $colsXml = '<cols>' .
            '<col min="1" max="1" width="8" customWidth="1"/>' .
            '<col min="2" max="2" width="22" customWidth="1"/>' .
            '<col min="3" max="3" width="24" customWidth="1"/>' .
            '<col min="4" max="4" width="18" customWidth="1"/>' .
            '<col min="5" max="5" width="16" customWidth="1"/>' .
            '<col min="6" max="6" width="22" customWidth="1"/>' .
            '<col min="7" max="7" width="26" customWidth="1"/>' .
            '<col min="8" max="8" width="16" customWidth="1"/>' .
            '<col min="9" max="9" width="48" customWidth="1"/>' .
            '<col min="10" max="10" width="18" customWidth="1"/>' .
            '<col min="11" max="11" width="30" customWidth="1"/>' .
            '<col min="12" max="12" width="22" customWidth="1"/>' .
            '<col min="13" max="13" width="65" customWidth="1"/>' .
            '</cols>';

        $headers = [
            'ID',
            'Tanggal & Waktu',
            'Nama Pelaku',
            'Username',
            'Peran (Role)',
            'Kategori Modul',
            'Aksi',
            'Tingkat Risiko',
            'Deskripsi Aktivitas',
            'IP Address',
            'Perangkat & Browser',
            'Objek Target',
            'Rincian Perubahan (JSON)',
        ];

        $headerRowXml = '<row r="1" ht="28" customHeight="1">';
        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
        foreach ($headers as $idx => $label) {
            $col = $colLetters[$idx];
            $escaped = htmlspecialchars($label, ENT_XML1, 'UTF-8');
            $headerRowXml .= "<c r=\"{$col}1\" t=\"inlineStr\" s=\"1\"><is><t>{$escaped}</t></is></c>";
        }
        $headerRowXml .= '</row>';

        $rowsXml = '';
        $rowIndex = 2;

        $query = $this->buildFilteredQuery($filters);
        $query->chunk(500, function ($logs) use (&$rowsXml, &$rowIndex, $colLetters): void {
            foreach ($logs as $log) {
                $isZebra = ($rowIndex % 2 === 0);
                $cellStyle = $isZebra ? 3 : 2;
                $centerStyle = $isZebra ? 5 : 4;

                $subjectText = $log->subject_type
                    ? class_basename($log->subject_type) . ' #' . $log->subject_id
                    : '-';

                $perubahanJson = $log->perubahan
                    ? json_encode($log->perubahan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : '-';

                $rowData = [
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
                ];

                $rowXml = "<row r=\"{$rowIndex}\">";
                foreach ($rowData as $cIdx => $cellVal) {
                    $cCol = $colLetters[$cIdx];
                    $cRef = "{$cCol}{$rowIndex}";
                    $cStyle = in_array($cIdx, [0, 4, 7], true) ? $centerStyle : $cellStyle;

                    // Khusus kolom 13 (Rincian Perubahan JSON), gunakan monospace style dengan wrapText
                    if ($cIdx === 12 && $log->perubahan) {
                        $cStyle = $isZebra ? 7 : 6;
                    }

                    if ($cIdx === 0 && is_numeric($cellVal)) {
                        $rowXml .= "<c r=\"{$cRef}\" s=\"{$cStyle}\"><v>{$cellVal}</v></c>";
                    } else {
                        $escapedVal = htmlspecialchars((string) $cellVal, ENT_XML1, 'UTF-8');
                        $rowXml .= "<c r=\"{$cRef}\" t=\"inlineStr\" s=\"{$cStyle}\"><is><t xml:space=\"preserve\">{$escapedVal}</t></is></c>";
                    }
                }
                $rowXml .= '</row>';
                $rowsXml .= $rowXml;
                $rowIndex++;
            }
        });

        $lastRow = max(1, $rowIndex - 1);
        $autoFilterRef = "A1:M{$lastRow}";

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetViews><sheetView tabSelected="1" workbookViewId="0">' .
            '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>' .
            '</sheetView></sheetViews>' .
            $colsXml .
            '<sheetData>' .
            $headerRowXml .
            $rowsXml .
            '</sheetData>' .
            "<autoFilter ref=\"{$autoFilterRef}\"/>" .
            '</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return response()->download($tempFile, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ])->deleteFileAfterSend(true);
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
