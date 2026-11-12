<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\User;
use App\Models\UserDokumen;
use App\Notifications\CopytradeAccountStatusChangedNotification;
use App\Notifications\NewCopytradeSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CopytradeService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Tambah akun copytrade baru untuk investor.
     *
     * @param array $data Data form termasuk modal riil, tier, nomor akun, dsb.
     * @param UploadedFile|null $suratPerjanjian Berkas surat perjanjian (wajib pada pendaftaran awal)
     */
    public function storeCopytrade(User $user, array $data, ?UploadedFile $suratPerjanjian): AkunCopytrade
    {
        return DB::transaction(function () use ($user, $data, $suratPerjanjian): AkunCopytrade {
            $tier = $data['tier_investasi'] ?? $data['tier'] ?? 'tier_1';

            $broker = $data['broker'] ?? match ($tier) {
                'tier_3' => 'XM',
                default  => 'HFM',
            };

            $tipeAkun = $data['tipe_akun'] ?? match ($tier) {
                'tier_1' => 'cent',
                'tier_3' => 'private_vps',
                default  => 'standard',
            };

            $existingValidAkun = AkunCopytrade::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'active'])
                ->latest('id')
                ->first();

            $existingBankAkun = AkunCopytrade::where('user_id', $user->id)
                ->whereNotNull('nama_bank')
                ->latest('id')
                ->first();

            $akunData = [
                'user_id'            => $user->id,
                'tier'               => $tier,
                'broker'             => $broker,
                'tipe_akun'          => $tipeAkun,
                'nomor_akun_trading' => $data['nomor_akun_trading'],
                'myfxbook_url'       => $data['myfxbook_url'] ?? null,
                'modal_riil'         => $data['modal_riil'],
                'tanggal_mulai'      => $data['tanggal_mulai_trading'] ?? $data['tanggal_mulai'] ?? now()->toDateString(),
                'nama_bank'          => !empty($data['nama_bank']) ? $data['nama_bank'] : ($existingBankAkun?->nama_bank ?? '-'),
                'nomor_rekening'     => !empty($data['nomor_rekening']) ? $data['nomor_rekening'] : ($existingBankAkun?->nomor_rekening ?? '-'),
                'atas_nama_rekening' => !empty($data['atas_nama_rekening']) ? $data['atas_nama_rekening'] : ($existingBankAkun?->atas_nama_rekening ?? $user->name),
                'status'             => 'pending',
            ];

            // Kredensial khusus Tier 3 (Private VPS)
            if (!empty($data['server_broker'])) {
                $akunData['server_broker'] = $data['server_broker'];
            }
            if (!empty($data['master_password'])) {
                $akunData['master_password'] = $data['master_password'];
            }
            if (!empty($data['investor_password'])) {
                $akunData['investor_password'] = $data['investor_password'];
            }

            // Simpan Surat Perjanjian jika dilampirkan, atau gunakan dari akun terdahulu yang sah
            if ($suratPerjanjian) {
                $storedPath = $suratPerjanjian->store("copytrade_agreements/{$user->id}", 'local');
                $akunData['surat_perjanjian_path'] = $storedPath;

                UserDokumen::updateOrCreate(
                    [
                        'user_id'      => $user->id,
                        'tipe_dokumen' => 'perjanjian',
                    ],
                    [
                        'file_path'          => $storedPath,
                        'file_name'          => $suratPerjanjian->getClientOriginalName(),
                        'mime_type'          => $suratPerjanjian->getClientMimeType() ?? $suratPerjanjian->getMimeType(),
                        'file_size'          => $suratPerjanjian->getSize(),
                        'status'             => 'pending',
                        'catatan_admin'      => null,
                        'is_quality_warning' => false,
                        'verified_at'        => null,
                        'verified_by'        => null,
                    ]
                );
            } elseif ($existingValidAkun?->surat_perjanjian_path) {
                $akunData['surat_perjanjian_path'] = $existingValidAkun->surat_perjanjian_path;
            }

            $akun = AkunCopytrade::create($akunData);

            // Audit Log: Pengajuan akun copytrade baru
            $this->auditLogService->record(
                kategori: 'copytrade',
                aksi: 'ajukan_copytrade',
                deskripsi: "Investor {$user->name} mendaftarkan akun copytrade baru (#{$akun->nomor_akun_trading}, {$akun->broker}, {$akun->tier}, \${$akun->modal_riil}).",
                subject: $akun,
                perubahan: [
                    'sesudah' => [
                        'tier'               => $akun->tier,
                        'broker'             => $akun->broker,
                        'tipe_akun'          => $akun->tipe_akun,
                        'nomor_akun_trading' => $akun->nomor_akun_trading,
                        'modal_riil'         => $akun->modal_riil,
                    ],
                ],
                tingkat: 'info',
                user: $user
            );

            // Kirim notifikasi database in-app ke seluruh administrator
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewCopytradeSubmittedNotification($user, $akun));
            }

            return $akun;
        });
    }

    /**
     * Verifikasi pendaftaran akun copy trade oleh Administrator.
     *
     * @throws ValidationException
     */
    public function verifyAccount(AkunCopytrade $akun, string $status, ?string $catatanAdmin, User $verifier): AkunCopytrade
    {
        if (!in_array($status, ['active', 'rejected'], true)) {
            throw new \InvalidArgumentException("Status keputusan copy trade tidak valid: {$status}");
        }

        if ($status === 'rejected' && empty(trim((string) $catatanAdmin))) {
            throw ValidationException::withMessages([
                'catatan_admin' => 'Catatan admin wajib diisi ketika menolak permohonan copy trade.',
            ]);
        }

        return DB::transaction(function () use ($akun, $status, $catatanAdmin, $verifier): AkunCopytrade {
            $sebelumStatus = $akun->status;

            $akun->update([
                'status'        => $status,
                'catatan_admin' => $status === 'active' ? ($catatanAdmin ?: null) : $catatanAdmin,
                'verified_by'   => $verifier->id,
                'verified_at'   => now(),
            ]);

            // Audit Log: Verifikasi akun copytrade
            $statusLabel = $status === 'active' ? 'Disetujui (Aktif)' : 'Ditolak';
            $actor = AuditLogService::formatActor($verifier);
            $target = AuditLogService::formatTargetUser($akun->user, $akun->user_id);
            $this->auditLogService->record(
                kategori: 'copytrade',
                aksi: 'verifikasi_copytrade',
                deskripsi: "{$actor} memverifikasi akun copytrade #{$akun->nomor_akun_trading} milik {$target} dengan status '{$statusLabel}'." . ($status === 'rejected' ? " Alasan: {$catatanAdmin}" : ''),
                subject: $akun,
                perubahan: [
                    'sebelum' => ['status' => $sebelumStatus],
                    'sesudah' => [
                        'status'        => $status,
                        'catatan_admin' => $status === 'rejected' ? $catatanAdmin : null,
                    ],
                    'meta' => [
                        'nomor_akun_trading' => $akun->nomor_akun_trading,
                        'broker'             => $akun->broker,
                        'modal_riil'         => $akun->modal_riil,
                    ],
                ],
                tingkat: $status === 'rejected' ? 'warning' : 'critical',
                user: $verifier
            );

            if ($status === 'active') {
                UserDokumen::where('user_id', $akun->user_id)
                    ->where('tipe_dokumen', 'perjanjian')
                    ->update([
                        'status'      => 'approved',
                        'verified_at' => now(),
                        'verified_by' => $verifier->id,
                    ]);
            } elseif ($status === 'rejected') {
                $hasApprovedAccount = $this->hasApprovedAccount($akun->user_id);

                if (!$hasApprovedAccount) {
                    $doc = UserDokumen::where('user_id', $akun->user_id)
                        ->where('tipe_dokumen', 'perjanjian')
                        ->where('status', '!=', 'approved')
                        ->first();

                    if ($doc) {
                        $doc->update([
                            'status'        => 'rejected',
                            'catatan_admin' => $catatanAdmin,
                            'verified_at'   => now(),
                            'verified_by'   => $verifier->id,
                        ]);
                    } elseif (!empty($akun->surat_perjanjian_path)) {
                        UserDokumen::create([
                            'user_id'      => $akun->user_id,
                            'tipe_dokumen' => 'perjanjian',
                            'file_path'    => $akun->surat_perjanjian_path,
                            'file_name'    => basename($akun->surat_perjanjian_path),
                            'mime_type'    => 'application/pdf',
                            'file_size'    => Storage::disk('local')->exists($akun->surat_perjanjian_path)
                                ? Storage::disk('local')->size($akun->surat_perjanjian_path)
                                : 0,
                            'status'        => 'rejected',
                            'catatan_admin' => $catatanAdmin,
                            'verified_at'   => now(),
                            'verified_by'   => $verifier->id,
                        ]);
                    }
                }
            }

            // Kirim notifikasi database in-app ke investor pemilik akun
            $akun->user->notify(new CopytradeAccountStatusChangedNotification($akun, $status, $catatanAdmin));

            return $akun;
        });
    }

    private const VERIFY_SORTS = ['created_at', 'nomor_akun_trading', 'tier', 'modal_riil', 'status'];

    private const ACTIVE_SORTS = ['created_at', 'nomor_akun_trading', 'tier', 'modal_riil', 'tanggal_mulai'];

    private const TIERS = ['tier_1', 'tier_2', 'tier_3'];

    /**
     * Jumlah akun per status untuk badge tab halaman Kelola Copytrade.
     */
    public function updateMyfxbookUrl(AkunCopytrade $akun, string $url, User $user): AkunCopytrade
    {
        if ($akun->user_id !== $user->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'myfxbook_url' => 'Anda tidak memiliki otorisasi untuk akun copytrade ini.',
            ]);
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host || !str_contains(strtolower($host), 'myfxbook.com')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'myfxbook_url' => 'Tautan Myfxbook harus berasal dari domain myfxbook.com.',
            ]);
        }

        $akun->update(['myfxbook_url' => $url]);

        $this->auditLogService->record(
            kategori: 'copytrade',
            aksi: 'update_myfxbook_url',
            deskripsi: "Investor {$user->name} memperbarui tautan Myfxbook akun #{$akun->nomor_akun_trading}.",
            subject: $akun,
            user: $user
        );

        return $akun;
    }

    public function getAdminCounts(): array
    {
        $rows = AkunCopytrade::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending'  => (int) ($rows['pending'] ?? 0),
            'rejected' => (int) ($rows['rejected'] ?? 0),
            'active'   => (int) ($rows['active'] ?? 0),
        ];
    }

    /**
     * Antrean verifikasi (pending/rejected). Default hanya pending, urutan default terbaru dulu (created_at desc).
     *
     * @param array{search:string,sort_by:string,sort_direction:string,per_page:int,page_name:string,status:string,tier:string} $f
     */
    public function paginateVerificationQueue(array $f): LengthAwarePaginator
    {
        $status = in_array($f['status'], ['pending', 'rejected', 'all'], true) ? $f['status'] : 'pending';
        $sort = in_array($f['sort_by'], self::VERIFY_SORTS, true) ? $f['sort_by'] : 'created_at';
        $dir = strtolower($f['sort_direction']) === 'asc' ? 'asc' : 'desc';

        $query = AkunCopytrade::query()
            ->with(['user', 'verifier'])
            ->whereIn('status', $status === 'all' ? ['pending', 'rejected'] : [$status]);

        $this->applyAdminSearch($query, $f['search']);

        if (in_array($f['tier'], self::TIERS, true)) {
            $query->where('tier', $f['tier']);
        }

        return $query->orderBy($sort, $dir)
            ->orderBy('id', $dir)
            ->paginate($f['per_page'], ['*'], $f['page_name'])
            ->withQueryString()
            ->through(fn (AkunCopytrade $akun) => $this->mapBaseRow($akun));
    }

    /**
     * Pemantauan akun aktif: saldo terakhir, profit riil, dan status pencatatan bulan ini.
     *
     * @param array{search:string,sort_by:string,sort_direction:string,per_page:int,page_name:string,tier:string,catatan:string} $f
     */
    public function paginateActiveAccounts(array $f): LengthAwarePaginator
    {
        $sort = in_array($f['sort_by'], self::ACTIVE_SORTS, true) ? $f['sort_by'] : 'tanggal_mulai';
        $dir = strtolower($f['sort_direction']) === 'asc' ? 'asc' : 'desc';

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $inThisMonth = fn ($q) => $q->whereBetween('periode_akhir', [$monthStart, $monthEnd]);

        $query = AkunCopytrade::query()
            ->with(['user', 'verifier', 'latestLaporanProfit'])
            ->withExists(['laporanProfit as recorded_this_month' => $inThisMonth])
            ->where('status', 'active');

        $this->applyAdminSearch($query, $f['search']);

        if (in_array($f['tier'], self::TIERS, true)) {
            $query->where('tier', $f['tier']);
        }

        if (!empty($f['username'])) {
            $query->whereHas('user', fn ($uq) => $uq->where('username', $f['username']));
        }

        if ($f['catatan'] === 'belum') {
            $query->whereDoesntHave('laporanProfit', $inThisMonth);
        }

        return $query->orderBy($sort, $dir)
            ->orderBy('id', $dir)
            ->paginate($f['per_page'], ['*'], $f['page_name'])
            ->withQueryString()
            ->through(function (AkunCopytrade $akun) {
                $latest = $akun->latestLaporanProfit;
                $modal = (float) $akun->modal_riil;
                $profit = $latest ? (float) $latest->profit_usd : null;
                $balance = $profit !== null ? $modal + $profit : null;

                return $this->mapBaseRow($akun) + [
                    'current_balance'           => $balance,
                    'current_balance_formatted' => $balance !== null ? '$' . number_format($balance, 2, '.', ',') : null,
                    'profit'                    => $profit !== null ? round($profit, 2) : null,
                    'profit_formatted'          => $profit !== null ? $this->signedUsd($profit) : null,
                    'profit_pct'                => ($profit !== null && $modal > 0) ? round(($profit / $modal) * 100.0, 2) : null,
                    'last_recorded_at'          => $latest?->periode_akhir ? tanggal_indo($latest->periode_akhir) : null,
                    'recorded_this_month'       => (bool) $akun->recorded_this_month,
                    'active_duration'           => $this->formatActiveDuration($akun->tanggal_mulai),
                ];
            });
    }

    /**
     * Detail akun untuk modal admin: kredensial (hanya tier 3) dan riwayat laporan profit (hanya akun aktif).
     */
    public function getAccountDetail(AkunCopytrade $akun): array
    {
        $credentials = $akun->tier === 'tier_3'
            ? [
                'server_broker'     => $akun->server_broker,
                'master_password'   => $akun->master_password,
                'investor_password' => $akun->investor_password,
            ]
            : null;

        $history = [];
        if ($akun->status === 'active') {
            $history = $akun->laporanProfit()
                ->orderByDesc('periode_ke')
                ->limit(24)
                ->get()
                ->map(function ($r) use ($akun) {
                    $modal = (float) $akun->modal_riil;
                    $profit = (float) $r->profit_usd;
                    $balance = $modal + $profit;
                    $profitPct = $modal > 0 ? round(($profit / $modal) * 100, 2) : 0;

                    return [
                        'id'                           => $r->id,
                        'periode_ke'                   => $r->periode_ke,
                        'tanggal'                      => $r->periode_akhir->toDateString(),
                        'tanggal_formatted'            => tanggal_indo($r->periode_akhir),
                        'balance'                      => $balance,
                        'balance_formatted'            => '$' . number_format($balance, 2, '.', ','),
                        'profit_usd'                   => $profit,
                        'profit_usd_formatted'         => $this->signedUsd($profit),
                        'keuntungan_nominal'           => $profit,
                        'keuntungan_nominal_formatted' => $this->signedUsd($profit),
                        'keuntungan_persen'            => $profitPct,
                        'status'                       => $r->status,
                        'status_tampil'                => $r->status_tampil,
                    ];
                })
                ->all();
        }

        return ['credentials' => $credentials, 'history' => $history];
    }

    private function applyAdminSearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('nomor_akun_trading', 'like', "%{$search}%")
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
        });
    }

    private function signedUsd(float $value): string
    {
        if (abs($value) < 0.005) {
            return '$0.00';
        }

        return ($value > 0 ? '+' : '-') . '$' . number_format(abs($value), 2, '.', ',');
    }

    private function formatActiveDuration($startedAt): string
    {
        if (!$startedAt) {
            return '-';
        }

        $startDate = $startedAt instanceof \Carbon\CarbonInterface ? $startedAt : \Carbon\Carbon::parse($startedAt);
        $months = (int) $startDate->diffInMonths(now());
        if ($months < 1) {
            return '< 1 bln';
        }

        $years = intdiv($months, 12);
        $rest = $months % 12;

        return $years > 0
            ? $years . ' thn' . ($rest > 0 ? " {$rest} bln" : '')
            : "{$rest} bln";
    }

    /**
     * Hitung urutan pengajuan investor ke-berapa (1-based).
     * Pendaftaran yang pernah ditolak (status = 'rejected') tidak dihitung.
     * Jika pendaftaran ini sendiri berstatus 'rejected', kembalikan null.
     */
    public function calculateSubmissionSequence(AkunCopytrade $akun): ?int
    {
        if ($akun->status === 'rejected') {
            return null;
        }

        return AkunCopytrade::query()
            ->where('user_id', $akun->user_id)
            ->where('status', '!=', 'rejected')
            ->where(function ($q) use ($akun) {
                if ($akun->created_at) {
                    $q->where('created_at', '<', $akun->created_at)
                        ->orWhere(function ($q2) use ($akun) {
                            $q2->where('created_at', '=', $akun->created_at)
                                ->where('id', '<=', $akun->id);
                        });
                } else {
                    $q->where('id', '<=', $akun->id);
                }
            })
            ->count();
    }

    /**
     * Periksa apakah investor memiliki setidaknya satu pengajuan akun copytrade yang telah disetujui (status = 'active').
     */
    public function hasApprovedAccount(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return AkunCopytrade::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Baris dasar daftar admin. SENGAJA tanpa master_password / investor_password.
     */
    private function mapBaseRow(AkunCopytrade $akun): array
    {
        $pengajuanKe = $this->calculateSubmissionSequence($akun);
        $hasApprovedAccount = $this->hasApprovedAccount($akun->user_id);
        $showPerjanjian = !($pengajuanKe !== null && $pengajuanKe >= 2 && $hasApprovedAccount);

        return [
            'id'   => $akun->id,
            'user' => [
                'id'       => $akun->user?->id,
                'name'     => $akun->user?->name ?? 'Investor',
                'username' => $akun->user?->username ?? '',
            ],
            'pengajuan_ke'         => $pengajuanKe,
            'has_approved_account' => $hasApprovedAccount,
            'show_perjanjian'      => $showPerjanjian,
            'tier'                 => $akun->tier,
            'tier_label'           => match ($akun->tier) {
                'tier_1' => 'Tier 1',
                'tier_2' => 'Tier 2',
                'tier_3' => 'Tier 3',
                default  => $akun->tier,
            },
            'broker'               => $akun->broker,
            'tipe_akun'            => $akun->tipe_akun,
            'nomor_akun_trading'   => $akun->nomor_akun_trading,
            'modal_riil'           => (float) $akun->modal_riil,
            'modal_riil_formatted' => '$' . number_format((float) $akun->modal_riil, 2, '.', ','),
            'tanggal_mulai'        => tanggal_indo($akun->tanggal_mulai),
            'nama_bank'            => $akun->nama_bank,
            'nomor_rekening'       => $akun->nomor_rekening,
            'atas_nama_rekening'   => $akun->atas_nama_rekening,
            'status'               => $akun->status,
            'catatan_admin'        => $akun->catatan_admin,
            'server_broker'        => $akun->server_broker,
            'myfxbook_url'         => $akun->myfxbook_url,
            'has_surat_perjanjian' => !empty($akun->surat_perjanjian_path),
            'surat_perjanjian_url' => $akun->surat_perjanjian_path ? route('documents.copytrade', $akun->id) : null,
            'created_at'           => tanggal_waktu_indo($akun->created_at),
            'verified_at'          => tanggal_waktu_indo($akun->verified_at),
            'verifier_name'        => $akun->verifier?->name,
        ];
    }

    /**
     * Hasilkan dokumen Word (DOCX) surat perjanjian kemitraan yang terisi otomatis
     * dengan data profil investor dan data formulir pendaftaran.
     *
     * @param User $user Pengguna investor
     * @param string $tier Tier investasi (tier_1, tier_2, tier_3)
     * @param array $formData Data dari formulir copytrade (nomor akun, modal, bank, dsb.)
     * @return string|null Path file temporer yang dihasilkan, atau null jika template tidak ditemukan
     */
    public function generatePerjanjianDocx(User $user, string $tier, array $formData = []): ?string
    {
        $fileName = match ($tier) {
            'tier_1' => 'tier_1_hfm.docx',
            'tier_2' => 'tier_2_hfm.docx',
            'tier_3' => 'tier_3_xm.docx',
            default  => null,
        };

        if (!$fileName) {
            return null;
        }

        $sourcePath = storage_path("app/templates/perjanjian/{$fileName}");
        if (!file_exists($sourcePath)) {
            return null;
        }

        Settings::setOutputEscapingEnabled(true);
        $template = new TemplateProcessor($sourcePath);

        $user->loadMissing('ahliWaris');

        $tanggalMulaiInput = !empty($formData['tanggal_mulai_trading'])
            ? $formData['tanggal_mulai_trading']
            : now()->toDateString();

        $tanggalMulaiFormatted = tanggal_indo($tanggalMulaiInput);

        try {
            $tanggalSelesaiFormatted = tanggal_indo(\Carbon\Carbon::parse($tanggalMulaiInput)->addYears(5));
        } catch (\Throwable) {
            $tanggalSelesaiFormatted = '-';
        }

        $tanggalSuratFormatted = tanggal_indo(now());
        $ttl = trim(
            ($user->tempat_lahir ? $user->tempat_lahir . ', ' : '') .
            ($user->tanggal_lahir ? tanggal_indo($user->tanggal_lahir) : '')
        );

        $existingAkun = AkunCopytrade::where('user_id', $user->id)->latest()->first();

        $namaBank = !empty($formData['nama_bank'])
            ? $formData['nama_bank']
            : ($existingAkun?->nama_bank ?? null);

        $nomorRekening = !empty($formData['nomor_rekening'])
            ? $formData['nomor_rekening']
            : ($existingAkun?->nomor_rekening ?? null);

        $atasNamaRekening = !empty($formData['atas_nama_rekening'])
            ? $formData['atas_nama_rekening']
            : ($existingAkun?->atas_nama_rekening ?? null);

        $rekeningParts = array_filter([
            $namaBank,
            $nomorRekening,
            !empty($atasNamaRekening) ? 'a.n ' . $atasNamaRekening : null,
        ]);
        $rekening = !empty($rekeningParts) ? implode(' - ', $rekeningParts) : '-';

        $serverBroker = !empty($formData['server_broker'])
            ? $formData['server_broker']
            : ($existingAkun?->server_broker ?? '-');

        $masterPassword = !empty($formData['master_password'])
            ? $formData['master_password']
            : ($existingAkun?->master_password ?? '-');

        $investorPassword = !empty($formData['investor_password'])
            ? $formData['investor_password']
            : ($existingAkun?->investor_password ?? '-');

        $nomorAkunTrading = !empty($formData['nomor_akun_trading'])
            ? $formData['nomor_akun_trading']
            : ($existingAkun?->nomor_akun_trading ?? '-');

        $modalRiilFormatted = !empty($formData['modal_riil'])
            ? '$' . number_format((float) $formData['modal_riil'], 2, '.', ',')
            : '-';

        $replacements = [
            'NAMA_LENGKAP'         => $user->name,
            'NAMA_INVESTOR_TTD'    => $user->name,
            'NIK'                  => $user->nik ?? '-',
            'TEMPAT_TANGGAL_LAHIR' => $ttl ?: '-',
            'ALAMAT_LENGKAP'       => $user->alamat ?? '-',
            'NOMOR_HP'             => $user->phone ?? '-',
            'EMAIL'                => $user->email ?? '-',
            'PEKERJAAN'            => $user->pekerjaan ?? '-',
            'TANGGAL_MULAI'        => $tanggalMulaiFormatted,
            'TANGGAL_SELESAI'      => $tanggalSelesaiFormatted,
            'NOMOR_AKUN_TRADING'   => $nomorAkunTrading,
            'SERVER_BROKER'        => $serverBroker,
            'MASTER_PASSWORD'      => $masterPassword,
            'INVESTOR_PASSWORD'    => $investorPassword,
            'ESTIMASI_MODAL'       => $modalRiilFormatted,
            'REKENING_PENCAIRAN'   => $rekening,
            'AHLI_WARIS_NAMA'      => $user->ahliWaris?->nama ?? '-',
            'AHLI_WARIS_HUBUNGAN'  => $user->ahliWaris?->hubungan_keluarga ?? '-',
            'AHLI_WARIS_PHONE'     => $user->ahliWaris?->no_hp ?? '-',
            'TANGGAL_SURAT'        => $tanggalSuratFormatted,
        ];

        foreach ($replacements as $search => $replace) {
            $template->setValue($search, (string) $replace);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'perjanjian_') . '.docx';
        $template->saveAs($tempFile);

        return $tempFile;

    }

    /**
     * Ekspor data antrean verifikasi copytrade ke format berkas XLSX.
     *
     * @param array<string, mixed> $filters
     */
    public function exportVerificationXlsx(array $filters = []): BinaryFileResponse
    {
        $columns = [
            ['label' => 'ID', 'width' => 8, 'align' => 'center'],
            ['label' => 'Tanggal Diajukan', 'width' => 20],
            ['label' => 'Nama Investor', 'width' => 25],
            ['label' => 'Username', 'width' => 18],
            ['label' => 'Pengajuan Ke-', 'width' => 16, 'align' => 'center'],
            ['label' => 'Tier', 'width' => 14, 'align' => 'center'],
            ['label' => 'Broker', 'width' => 20],
            ['label' => 'Tipe Akun', 'width' => 16, 'align' => 'center'],
            ['label' => 'Nomor Akun Trading', 'width' => 22, 'type' => 'text'],
            ['label' => 'Modal Riil (USD)', 'width' => 18, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Nama Bank', 'width' => 20],
            ['label' => 'Nomor Rekening', 'width' => 22, 'type' => 'text'],
            ['label' => 'Atas Nama Rekening', 'width' => 25],
            ['label' => 'Status Verifikasi', 'width' => 18, 'align' => 'center'],
            ['label' => 'Diverifikasi Oleh', 'width' => 22],
            ['label' => 'Waktu Verifikasi', 'width' => 20],
            ['label' => 'Catatan Admin', 'width' => 35],
        ];

        $search = trim((string) ($filters['ver_search'] ?? $filters['search'] ?? ''));
        $status = trim((string) ($filters['ver_status'] ?? $filters['status'] ?? ''));
        $tier = trim((string) ($filters['ver_tier'] ?? $filters['tier'] ?? ''));
        $sort = $filters['ver_sort_by'] ?? $filters['sort_by'] ?? 'created_at';
        $dir = strtolower($filters['ver_sort_direction'] ?? $filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (!in_array($sort, self::VERIFY_SORTS, true)) {
            $sort = 'created_at';
        }

        $query = AkunCopytrade::query()
            ->with(['user', 'verifier'])
            ->whereIn('status', in_array($status, ['pending', 'rejected', 'all'], true) ? ($status === 'all' ? ['pending', 'rejected'] : [$status]) : ['pending']);

        $this->applyAdminSearch($query, $search);

        if (in_array($tier, self::TIERS, true)) {
            $query->where('tier', $tier);
        }

        $query->orderBy($sort, $dir)->orderBy('id', $dir);

        // Audit Log
        $actor = Auth::user();
        if ($actor) {
            $this->auditLogService->record(
                kategori: 'copytrade',
                aksi: 'ekspor_data',
                deskripsi: AuditLogService::formatActor($actor) . ' mengekspor antrean verifikasi copytrade ke format XLSX.',
                tingkat: 'info',
                user: $actor
            );
        }

        $xlsxService = app(XlsxExportService::class);

        return $xlsxService->download('verifikasi_copytrade_yzd', 'Verifikasi Copytrade', $columns, function ($addRow) use ($query): void {
            $query->chunk(500, function ($accounts) use ($addRow): void {
                foreach ($accounts as $akun) {
                    $pengajuanKe = $this->calculateSubmissionSequence($akun);
                    $tierLabel = match ($akun->tier) {
                        'tier_1' => 'Tier 1',
                        'tier_2' => 'Tier 2',
                        'tier_3' => 'Tier 3',
                        default  => $akun->tier,
                    };

                    $statusLabel = match ($akun->status) {
                        'active'   => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default    => 'Menunggu Verifikasi',
                    };

                    $addRow([
                        (string) $akun->id,
                        $akun->created_at ? $akun->created_at->format('Y-m-d H:i') : '-',
                        $akun->user?->name ?? 'Investor',
                        $akun->user?->username ? '@' . $akun->user->username : '-',
                        $pengajuanKe !== null ? "Pengajuan ke-{$pengajuanKe}" : '-',
                        $tierLabel,
                        $akun->broker,
                        strtoupper($akun->tipe_akun),
                        (string) $akun->nomor_akun_trading,
                        (float) $akun->modal_riil,
                        $akun->nama_bank ?? '-',
                        (string) ($akun->nomor_rekening ?? '-'),
                        $akun->atas_nama_rekening ?? '-',
                        $statusLabel,
                        $akun->verifier?->name ?? '-',
                        $akun->verified_at ? $akun->verified_at->format('Y-m-d H:i') : '-',
                        $akun->catatan_admin ?? '-',
                    ]);
                }
            });
        });
    }

    /**
     * Ekspor data pemantauan akun copytrade aktif ke format berkas XLSX.
     *
     * @param array<string, mixed> $filters
     */
    public function exportActiveAccountsXlsx(array $filters = []): BinaryFileResponse
    {
        $columns = [
            ['label' => 'ID', 'width' => 8, 'align' => 'center'],
            ['label' => 'Mulai Investasi', 'width' => 18],
            ['label' => 'Durasi Aktif', 'width' => 16, 'align' => 'center'],
            ['label' => 'Nama Investor', 'width' => 25],
            ['label' => 'Username', 'width' => 18],
            ['label' => 'Tier', 'width' => 14, 'align' => 'center'],
            ['label' => 'Broker', 'width' => 20],
            ['label' => 'Tipe Akun', 'width' => 16, 'align' => 'center'],
            ['label' => 'Nomor Akun Trading', 'width' => 22, 'type' => 'text'],
            ['label' => 'Modal Awal (USD)', 'width' => 18, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Profit Terakhir (USD)', 'width' => 20, 'align' => 'right', 'type' => 'number'],
            ['label' => 'ROI (%)', 'width' => 14, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Saldo Terakhir (USD)', 'width' => 20, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Periode Catatan Terakhir', 'width' => 24],
            ['label' => 'Sudah Lapor Bulan Ini', 'width' => 22, 'align' => 'center'],
            ['label' => 'Tautan Myfxbook', 'width' => 45],
        ];

        $search = trim((string) ($filters['akt_search'] ?? $filters['search'] ?? ''));
        $tier = trim((string) ($filters['akt_tier'] ?? $filters['tier'] ?? ''));
        $catatan = trim((string) ($filters['akt_catatan'] ?? $filters['catatan'] ?? ''));
        $username = trim((string) ($filters['akt_username'] ?? $filters['username'] ?? ''));
        $sort = $filters['akt_sort_by'] ?? $filters['sort_by'] ?? 'tanggal_mulai';
        $dir = strtolower($filters['akt_sort_direction'] ?? $filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (!in_array($sort, self::ACTIVE_SORTS, true)) {
            $sort = 'tanggal_mulai';
        }

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $inThisMonth = fn ($q) => $q->whereBetween('periode_akhir', [$monthStart, $monthEnd]);

        $query = AkunCopytrade::query()
            ->with(['user', 'verifier', 'latestLaporanProfit'])
            ->withExists(['laporanProfit as recorded_this_month' => $inThisMonth])
            ->where('status', 'active');

        $this->applyAdminSearch($query, $search);

        if (in_array($tier, self::TIERS, true)) {
            $query->where('tier', $tier);
        }

        if (!empty($username)) {
            $query->whereHas('user', fn ($uq) => $uq->where('username', $username));
        }

        if ($catatan === 'belum') {
            $query->whereDoesntHave('laporanProfit', $inThisMonth);
        }

        $query->orderBy($sort, $dir)->orderBy('id', $dir);

        // Audit Log
        $actor = Auth::user();
        if ($actor) {
            $this->auditLogService->record(
                kategori: 'copytrade',
                aksi: 'ekspor_data',
                deskripsi: AuditLogService::formatActor($actor) . ' mengekspor daftar akun copytrade aktif ke format XLSX.',
                tingkat: 'info',
                user: $actor
            );
        }

        $xlsxService = app(XlsxExportService::class);

        return $xlsxService->download('akun_copytrade_aktif_yzd', 'Akun Aktif', $columns, function ($addRow) use ($query): void {
            $query->chunk(500, function ($accounts) use ($addRow): void {
                foreach ($accounts as $akun) {
                    $latest = $akun->latestLaporanProfit;
                    $modal = (float) $akun->modal_riil;
                    $profit = $latest ? (float) $latest->profit_usd : null;
                    $balance = $profit !== null ? $modal + $profit : null;
                    $profitPct = ($profit !== null && $modal > 0) ? round(($profit / $modal) * 100.0, 2) : null;

                    $tierLabel = match ($akun->tier) {
                        'tier_1' => 'Tier 1',
                        'tier_2' => 'Tier 2',
                        'tier_3' => 'Tier 3',
                        default  => $akun->tier,
                    };

                    $addRow([
                        (string) $akun->id,
                        $akun->tanggal_mulai ? \Carbon\Carbon::parse($akun->tanggal_mulai)->format('Y-m-d') : '-',
                        $this->formatActiveDuration($akun->tanggal_mulai),
                        $akun->user?->name ?? 'Investor',
                        $akun->user?->username ? '@' . $akun->user->username : '-',
                        $tierLabel,
                        $akun->broker,
                        strtoupper($akun->tipe_akun),
                        (string) $akun->nomor_akun_trading,
                        $modal,
                        $profit !== null ? round($profit, 2) : 0,
                        $profitPct !== null ? $profitPct : 0,
                        $balance !== null ? round($balance, 2) : $modal,
                        $latest?->periode_akhir ? \Carbon\Carbon::parse($latest->periode_akhir)->format('Y-m-d') : '-',
                        $akun->recorded_this_month ? 'Ya' : 'Belum',
                        $akun->myfxbook_url ?? '-',
                    ]);
                }
            });
        });
    }
}
