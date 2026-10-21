<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\User;
use App\Models\UserDokumen;
use App\Notifications\CopytradeAccountStatusChangedNotification;
use App\Notifications\NewCopytradeSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\TemplateProcessor;

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
                'tier_2', 'tier_3' => 'XM',
                default            => 'HFM',
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
            $this->auditLogService->record(
                kategori: 'copytrade',
                aksi: 'verifikasi_copytrade',
                deskripsi: "Administrator {$verifier->name} memverifikasi akun copytrade #{$akun->nomor_akun_trading} milik {$akun->user->name} dengan status '{$statusLabel}'." . ($status === 'rejected' ? " Alasan: {$catatanAdmin}" : ''),
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
        $inThisMonth = fn ($q) => $q->whereBetween('tanggal_pencatatan', [$monthStart, $monthEnd]);

        $query = AkunCopytrade::query()
            ->with(['user', 'verifier', 'latestRiwayatBalance'])
            ->withExists(['riwayatBalance as recorded_this_month' => $inThisMonth])
            ->where('status', 'active');

        $this->applyAdminSearch($query, $f['search']);

        if (in_array($f['tier'], self::TIERS, true)) {
            $query->where('tier', $f['tier']);
        }

        if (!empty($f['username'])) {
            $query->whereHas('user', fn ($uq) => $uq->where('username', $f['username']));
        }

        if ($f['catatan'] === 'belum') {
            $query->whereDoesntHave('riwayatBalance', $inThisMonth);
        }

        return $query->orderBy($sort, $dir)
            ->orderBy('id', $dir)
            ->paginate($f['per_page'], ['*'], $f['page_name'])
            ->withQueryString()
            ->through(function (AkunCopytrade $akun) {
                $latest = $akun->latestRiwayatBalance;
                $modal = (float) $akun->modal_riil;
                $balance = $latest ? (float) $latest->balance : null;
                $profit = $balance !== null ? $balance - $modal : null;

                return $this->mapBaseRow($akun) + [
                    'current_balance'           => $balance,
                    'current_balance_formatted' => $balance !== null ? '$' . number_format($balance, 2, '.', ',') : null,
                    'profit'                    => $profit !== null ? round($profit, 2) : null,
                    'profit_formatted'          => $profit !== null ? $this->signedUsd($profit) : null,
                    'profit_pct'                => ($profit !== null && $modal > 0) ? round(($profit / $modal) * 100.0, 2) : null,
                    'last_recorded_at'          => $latest?->tanggal_pencatatan ? tanggal_indo($latest->tanggal_pencatatan) : null,
                    'recorded_this_month'       => (bool) $akun->recorded_this_month,
                    'active_duration'           => $this->formatActiveDuration($akun->tanggal_mulai),
                ];
            });
    }

    /**
     * Detail akun untuk modal admin: kredensial (hanya tier 3) dan riwayat saldo (hanya akun aktif).
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
            $history = $akun->riwayatBalance()
                ->orderByDesc('tanggal_pencatatan')
                ->orderByDesc('id')
                ->limit(24)
                ->get()
                ->map(fn ($r) => [
                    'id'                           => $r->id,
                    'tanggal'                      => $r->tanggal_pencatatan->toDateString(),
                    'tanggal_formatted'            => tanggal_indo($r->tanggal_pencatatan),
                    'balance'                      => (float) $r->balance,
                    'balance_formatted'            => '$' . number_format((float) $r->balance, 2, '.', ','),
                    'keuntungan_nominal'           => (float) $r->keuntungan_nominal,
                    'keuntungan_nominal_formatted' => $this->signedUsd((float) $r->keuntungan_nominal),
                    'keuntungan_persen'            => (float) $r->keuntungan_persen,
                ])
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
            'tier_2' => 'tier_2_xm.docx',
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
}
