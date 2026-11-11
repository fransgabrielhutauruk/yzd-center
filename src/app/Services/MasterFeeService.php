<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\LaporanProfitBulanan;
use App\Models\PengaturanMasterFee;
use App\Models\User;
use App\Notifications\MasterFeeCorrectionNotification;
use App\Notifications\MasterFeeIssuedNotification;
use App\Notifications\MasterFeeProofSubmittedNotification;
use App\Notifications\MasterFeeRejectedNotification;
use App\Notifications\MasterFeeVerifiedNotification;
use App\Support\ProfitPeriod;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MasterFeeService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Menghasilkan nomor laporan profit / master fee berurutan dengan format MFE-YYYYMM-XXXX.
     */
    public function generateNomor(Carbon $date): string
    {
        $yearMonth = $date->format('Ym');
        $prefix = "MFE-{$yearMonth}-";

        $lastNumber = LaporanProfitBulanan::withTrashed()
            ->where('nomor', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('nomor')
            ->value('nomor');

        $sequence = 1;
        if ($lastNumber && preg_match('/-(\d{4})$/', $lastNumber, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('MFE-%s-%04d', $yearMonth, $sequence);
    }

    /**
     * Mengambil daftar akun milik user yang memiliki periode laporan profit yang telah dibuka.
     *
     * @return array<int, array{akun: AkunCopytrade, next_period: array{ke: int, awal: Carbon, akhir: Carbon, open_date: Carbon}}>
     */
    public function availableReportableAccounts(User $user): array
    {
        $today = Carbon::now();
        $accounts = AkunCopytrade::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('id', 'asc')
            ->get();

        $available = [];
        foreach ($accounts as $akun) {
            $next = ProfitPeriod::nextReportablePeriod($akun, $today);
            if ($next !== null) {
                $available[] = [
                    'akun'        => $akun,
                    'next_period' => $next,
                ];
            }
        }

        return $available;
    }

    /**
     * Submit laporan profit bulanan oleh member.
     */
    public function submitReport(
        AkunCopytrade $akun,
        array $data,
        UploadedFile $buktiMyfxbook,
        ?UploadedFile $buktiWd,
        User $user
    ): LaporanProfitBulanan {
        if ($akun->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'akun_copytrade_id' => 'Anda tidak memiliki otorisasi untuk akun copytrade ini.',
            ]);
        }

        if ($akun->status !== 'active') {
            throw ValidationException::withMessages([
                'akun_copytrade_id' => 'Laporan profit hanya dapat dibuat untuk akun copytrade yang telah aktif.',
            ]);
        }

        if (empty(trim((string) $akun->myfxbook_url))) {
            throw ValidationException::withMessages([
                'akun_copytrade_id' => 'Tautan Myfxbook akun copytrade ini belum dilengkapi. Silakan lengkapi tautan terlebih dahulu di menu Portofolio Investasi.',
            ]);
        }

        $today = Carbon::now();
        $nextPeriod = ProfitPeriod::nextReportablePeriod($akun, $today);
        if ($nextPeriod === null) {
            throw ValidationException::withMessages([
                'periode' => 'Belum ada periode laporan profit yang dapat diinput saat ini untuk akun ini.',
            ]);
        }

        // Validasi urutan periode: jika user mengirim periode_ke, harus cocok dengan periode berikutnya
        if (!empty($data['periode_ke']) && (int) $data['periode_ke'] !== $nextPeriod['ke']) {
            throw ValidationException::withMessages([
                'periode_ke' => "Laporan harus diinput berurutan mulai dari periode ke-{$nextPeriod['ke']}.",
            ]);
        }

        $settings = PengaturanMasterFee::current();
        $feePersen = (float) $settings->fee_persen;
        $wdMaksPersen = (float) ($settings->wd_maks_persen ?? 60);

        $profitUsd = round((float) $data['profit_usd'], 2);

        $wdUsd = null;
        $wdIdr = null;
        $kursRiil = null;
        $feeUsd = 0.0;
        $feeIdr = 0;
        $status = LaporanProfitBulanan::STATUS_TANPA_FEE;

        if ($profitUsd > 0) {
            if (!isset($data['wd_usd']) || !is_numeric($data['wd_usd'])) {
                throw ValidationException::withMessages([
                    'wd_usd' => 'Nominal penarikan broker (USD) wajib diisi ketika profit di atas $0.',
                ]);
            }
            if (!isset($data['wd_idr']) || !is_numeric($data['wd_idr'])) {
                throw ValidationException::withMessages([
                    'wd_idr' => 'Nominal Rupiah diterima (IDR) wajib diisi ketika profit di atas $0.',
                ]);
            }
            if (!$buktiWd) {
                throw ValidationException::withMessages([
                    'bukti_wd' => 'Screenshot bukti penarikan (WD) broker wajib diunggah ketika profit di atas $0.',
                ]);
            }

            $wdUsd = round((float) $data['wd_usd'], 2);
            $wdIdr = (int) $data['wd_idr'];

            $wdMin = round($profitUsd * $feePersen / 100, 2);
            $wdMax = round($profitUsd * $wdMaksPersen / 100, 2);

            if ($wdUsd < $wdMin || $wdUsd > $wdMax) {
                throw ValidationException::withMessages([
                    'wd_usd' => "Nominal penarikan USD (\${$wdUsd}) harus berada di antara batas minimal fee \${$wdMin} ({$feePersen}%) dan batas maksimal \${$wdMax} ({$wdMaksPersen}%).",
                ]);
            }

            if ($wdIdr <= 0) {
                throw ValidationException::withMessages([
                    'wd_idr' => 'Nominal Rupiah (IDR) yang diterima harus lebih besar dari 0.',
                ]);
            }

            $kursRiil = round($wdIdr / $wdUsd, 2);
            $feeUsd = round($profitUsd * $feePersen / 100, 2);
            $feeIdr = (int) round($feeUsd * ($wdIdr / $wdUsd));
            $status = LaporanProfitBulanan::STATUS_BELUM_DIBAYAR;
        }

        return DB::transaction(function () use (
            $akun,
            $user,
            $nextPeriod,
            $profitUsd,
            $buktiMyfxbook,
            $wdUsd,
            $wdIdr,
            $buktiWd,
            $kursRiil,
            $feePersen,
            $feeUsd,
            $feeIdr,
            $status
        ): LaporanProfitBulanan {
            // Lock akun untuk mencegah race condition pembuatan laporan ganda
            AkunCopytrade::where('id', $akun->id)->lockForUpdate()->first();

            // Pengecekan duplikat laporan aktif untuk (akun, periode_ke)
            $existingReport = LaporanProfitBulanan::where('akun_copytrade_id', $akun->id)
                ->where('periode_ke', $nextPeriod['ke'])
                ->first();

            if ($existingReport) {
                throw ValidationException::withMessages([
                    'periode' => "Laporan untuk periode ke-{$nextPeriod['ke']} sudah pernah dibuat.",
                ]);
            }

            $nomor = $this->generateNomor(Carbon::now());

            $extMb = $buktiMyfxbook->getClientOriginalExtension() ?: 'jpg';
            $filenameMb = 'myfxbook_' . time() . '_' . uniqid() . '.' . $extMb;
            $myfxbookPath = $buktiMyfxbook->storeAs("master_fee/myfxbook/{$user->id}", $filenameMb, 'local');

            $wdPath = null;
            if ($buktiWd) {
                $extWd = $buktiWd->getClientOriginalExtension() ?: 'jpg';
                $filenameWd = 'wd_' . time() . '_' . uniqid() . '.' . $extWd;
                $wdPath = $buktiWd->storeAs("master_fee/wd/{$user->id}", $filenameWd, 'local');
            }

            $laporan = LaporanProfitBulanan::create([
                'nomor'               => $nomor,
                'akun_copytrade_id'   => $akun->id,
                'user_id'             => $user->id,
                'periode_ke'          => $nextPeriod['ke'],
                'periode_awal'        => $nextPeriod['awal']->toDateString(),
                'periode_akhir'       => $nextPeriod['akhir']->toDateString(),
                'jatuh_tempo'         => $nextPeriod['open_date']->toDateString(),
                'profit_usd'          => $profitUsd,
                'bukti_myfxbook_path' => $myfxbookPath,
                'wd_usd'              => $wdUsd,
                'wd_idr'              => $wdIdr,
                'bukti_wd_path'       => $wdPath,
                'kurs_riil'           => $kursRiil,
                'fee_persen'          => $feePersen,
                'fee_usd'             => $feeUsd,
                'fee_idr'             => $feeIdr,
                'status'              => $status,
                'perlu_koreksi'       => false,
            ]);

            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'buat_laporan',
                deskripsi: "Member {$user->name} membuat laporan profit {$laporan->nomor} periode {$nextPeriod['ke']} (\${$profitUsd}). Fee: \${$feeUsd} (Rp" . number_format($feeIdr, 0, ',', '.') . ').',
                subject: $laporan,
                user: $user
            );

            if ($feeUsd > 0) {
                $user->notify(new MasterFeeIssuedNotification($laporan));
            }

            return $laporan;
        });
    }

    /**
     * Memperbarui laporan profit yang berstatus editable (belum dibayar, ditolak, atau perlu koreksi).
     */
    public function updateReport(
        LaporanProfitBulanan $laporan,
        array $data,
        ?UploadedFile $buktiMyfxbook,
        ?UploadedFile $buktiWd,
        User $user
    ): LaporanProfitBulanan {
        if ($laporan->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'laporan' => 'Anda tidak memiliki otorisasi untuk laporan ini.',
            ]);
        }

        if (!$laporan->is_editable) {
            throw ValidationException::withMessages([
                'laporan' => 'Laporan yang sedang menunggu verifikasi atau sudah lunas tidak dapat diubah.',
            ]);
        }

        $settings = PengaturanMasterFee::current();
        $feePersen = (float) $laporan->fee_persen; // Pertahankan persen yang tersimpan di laporan
        $wdMaksPersen = (float) ($settings->wd_maks_persen ?? 60);

        $profitUsd = round((float) $data['profit_usd'], 2);

        $wdUsd = null;
        $wdIdr = null;
        $kursRiil = null;
        $feeUsd = 0.0;
        $feeIdr = 0;
        $status = LaporanProfitBulanan::STATUS_TANPA_FEE;

        if ($profitUsd > 0) {
            if (!isset($data['wd_usd']) || !is_numeric($data['wd_usd'])) {
                throw ValidationException::withMessages([
                    'wd_usd' => 'Nominal penarikan broker (USD) wajib diisi ketika profit di atas $0.',
                ]);
            }
            if (!isset($data['wd_idr']) || !is_numeric($data['wd_idr'])) {
                throw ValidationException::withMessages([
                    'wd_idr' => 'Nominal Rupiah diterima (IDR) wajib diisi ketika profit di atas $0.',
                ]);
            }
            if (!$buktiWd && empty($laporan->bukti_wd_path)) {
                throw ValidationException::withMessages([
                    'bukti_wd' => 'Screenshot bukti penarikan (WD) broker wajib diunggah ketika profit di atas $0.',
                ]);
            }

            $wdUsd = round((float) $data['wd_usd'], 2);
            $wdIdr = (int) $data['wd_idr'];

            $wdMin = round($profitUsd * $feePersen / 100, 2);
            $wdMax = round($profitUsd * $wdMaksPersen / 100, 2);

            if ($wdUsd < $wdMin || $wdUsd > $wdMax) {
                throw ValidationException::withMessages([
                    'wd_usd' => "Nominal penarikan USD (\${$wdUsd}) harus berada di antara batas minimal fee \${$wdMin} ({$feePersen}%) dan batas maksimal \${$wdMax} ({$wdMaksPersen}%).",
                ]);
            }

            if ($wdIdr <= 0) {
                throw ValidationException::withMessages([
                    'wd_idr' => 'Nominal Rupiah (IDR) yang diterima harus lebih besar dari 0.',
                ]);
            }

            $kursRiil = round($wdIdr / $wdUsd, 2);
            $feeUsd = round($profitUsd * $feePersen / 100, 2);
            $feeIdr = (int) round($feeUsd * ($wdIdr / $wdUsd));
            $status = LaporanProfitBulanan::STATUS_BELUM_DIBAYAR;
        }

        return DB::transaction(function () use (
            $laporan,
            $user,
            $profitUsd,
            $buktiMyfxbook,
            $wdUsd,
            $wdIdr,
            $buktiWd,
            $kursRiil,
            $feeUsd,
            $feeIdr,
            $status
        ): LaporanProfitBulanan {
            $updatePayload = [
                'profit_usd'      => $profitUsd,
                'wd_usd'          => $wdUsd,
                'wd_idr'          => $wdIdr,
                'kurs_riil'       => $kursRiil,
                'fee_usd'         => $feeUsd,
                'fee_idr'         => $feeIdr,
                'status'          => $status,
                'perlu_koreksi'   => false,
                'catatan_koreksi' => null,
            ];

            if ($buktiMyfxbook) {
                if ($laporan->bukti_myfxbook_path) {
                    Storage::disk('local')->delete($laporan->bukti_myfxbook_path);
                }
                $extMb = $buktiMyfxbook->getClientOriginalExtension() ?: 'jpg';
                $filenameMb = 'myfxbook_' . time() . '_' . uniqid() . '.' . $extMb;
                $updatePayload['bukti_myfxbook_path'] = $buktiMyfxbook->storeAs("master_fee/myfxbook/{$user->id}", $filenameMb, 'local');
            }

            if ($profitUsd <= 0) {
                if ($laporan->bukti_wd_path) {
                    Storage::disk('local')->delete($laporan->bukti_wd_path);
                    $updatePayload['bukti_wd_path'] = null;
                }
            } elseif ($buktiWd) {
                if ($laporan->bukti_wd_path) {
                    Storage::disk('local')->delete($laporan->bukti_wd_path);
                }
                $extWd = $buktiWd->getClientOriginalExtension() ?: 'jpg';
                $filenameWd = 'wd_' . time() . '_' . uniqid() . '.' . $extWd;
                $updatePayload['bukti_wd_path'] = $buktiWd->storeAs("master_fee/wd/{$user->id}", $filenameWd, 'local');
            }

            $laporan->update($updatePayload);

            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'ubah_laporan',
                deskripsi: "Member {$user->name} memperbarui data laporan profit {$laporan->nomor} (\${$profitUsd}). Fee baru: \${$feeUsd}.",
                subject: $laporan,
                user: $user
            );

            return $laporan->fresh();
        });
    }

    /**
     * Menghapus laporan profit (hanya jika editable dan merupakan laporan terakhir pada akun tersebut).
     */
    public function deleteReport(LaporanProfitBulanan $laporan, User $user): bool
    {
        if ($laporan->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'laporan' => 'Anda tidak memiliki otorisasi untuk menghapus laporan ini.',
            ]);
        }

        if (!$laporan->is_editable) {
            throw ValidationException::withMessages([
                'laporan' => 'Laporan yang sedang menunggu verifikasi atau sudah lunas tidak dapat dihapus.',
            ]);
        }

        $maxPeriodeKe = LaporanProfitBulanan::where('akun_copytrade_id', $laporan->akun_copytrade_id)->max('periode_ke');
        if ((int) $laporan->periode_ke !== (int) $maxPeriodeKe) {
            throw ValidationException::withMessages([
                'laporan' => 'Hanya laporan periode paling akhir yang dapat dihapus agar urutan periode tetap terjaga.',
            ]);
        }

        return DB::transaction(function () use ($laporan, $user) {
            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'hapus_laporan',
                deskripsi: "Member {$user->name} menghapus laporan profit {$laporan->nomor} periode ke-{$laporan->periode_ke}.",
                subject: $laporan,
                user: $user
            );

            return (bool) $laporan->delete();
        });
    }

    /**
     * Mengunggah bukti transfer pembayaran master fee oleh member.
     */
    public function submitProof(LaporanProfitBulanan $laporan, UploadedFile $file, User $user): LaporanProfitBulanan
    {
        if ($laporan->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'bukti' => 'Anda tidak memiliki otorisasi untuk tagihan ini.',
            ]);
        }

        if (!in_array($laporan->status, [LaporanProfitBulanan::STATUS_BELUM_DIBAYAR, LaporanProfitBulanan::STATUS_DITOLAK], true)) {
            throw ValidationException::withMessages([
                'bukti' => 'Bukti pembayaran hanya dapat diunggah untuk master fee yang belum dibayar atau ditolak.',
            ]);
        }

        if ($laporan->fee_usd <= 0) {
            throw ValidationException::withMessages([
                'bukti' => 'Laporan profit ini tidak memiliki kewajiban fee Master Fee.',
            ]);
        }

        return DB::transaction(function () use ($laporan, $file, $user) {
            if ($laporan->bukti_transfer_path) {
                Storage::disk('local')->delete($laporan->bukti_transfer_path);
            }

            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = 'proof_' . $laporan->id . '_' . time() . '.' . $ext;
            $path = $file->storeAs("master_fee/transfers/{$user->id}", $filename, 'local');

            $laporan->update([
                'bukti_transfer_path' => $path,
                'status'              => LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI,
                'dibayar_at'          => Carbon::now(),
                'catatan_admin'       => null,
            ]);

            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'unggah_bukti',
                deskripsi: "Member {$user->name} mengunggah bukti pembayaran Master Fee {$laporan->nomor} (\${$laporan->fee_usd}).",
                subject: $laporan,
                user: $user
            );

            // Notifikasi ke seluruh administrator
            $admins = User::where('role', 'administrator')->get();
            foreach ($admins as $admin) {
                $admin->notify(new MasterFeeProofSubmittedNotification($laporan));
            }

            return $laporan->fresh();
        });
    }

    /**
     * Verifikasi master fee oleh admin (status menjadi lunas).
     */
    public function verify(LaporanProfitBulanan|int $id, User $admin): LaporanProfitBulanan
    {
        $laporan = $id instanceof LaporanProfitBulanan ? $id : LaporanProfitBulanan::findOrFail($id);

        return DB::transaction(function () use ($laporan, $admin) {
            $laporan->update([
                'status'        => LaporanProfitBulanan::STATUS_LUNAS,
                'verified_by'   => $admin->id,
                'verified_at'   => Carbon::now(),
                'dibayar_at'    => $laporan->dibayar_at ?? Carbon::now(),
                'perlu_koreksi' => false,
            ]);

            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'verifikasi',
                deskripsi: "Admin {$admin->name} memverifikasi pembayaran Master Fee {$laporan->nomor} (\${$laporan->fee_usd}) menjadi LUNAS.",
                subject: $laporan,
                user: $admin
            );

            $laporan->user?->notify(new MasterFeeVerifiedNotification($laporan));

            return $laporan->fresh();
        });
    }

    /**
     * Menolak bukti pembayaran tagihan oleh admin.
     */
    public function reject(LaporanProfitBulanan|int $id, User $admin, string $alasan): LaporanProfitBulanan
    {
        $laporan = $id instanceof LaporanProfitBulanan ? $id : LaporanProfitBulanan::findOrFail($id);

        return DB::transaction(function () use ($laporan, $admin, $alasan) {
            $laporan->update([
                'status'        => LaporanProfitBulanan::STATUS_DITOLAK,
                'catatan_admin' => trim($alasan),
                'verified_by'   => $admin->id,
                'verified_at'   => Carbon::now(),
            ]);

            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'tolak',
                deskripsi: "Admin {$admin->name} menolak pembayaran Master Fee {$laporan->nomor}. Alasan: {$alasan}",
                subject: $laporan,
                user: $admin
            );

            $laporan->user?->notify(new MasterFeeRejectedNotification($laporan, $alasan));

            return $laporan->fresh();
        });
    }

    /**
     * Menandai laporan perlu koreksi oleh admin agar member merevisi datanya.
     */
    public function flagCorrection(LaporanProfitBulanan|int $id, User $admin, string $alasan): LaporanProfitBulanan
    {
        $laporan = $id instanceof LaporanProfitBulanan ? $id : LaporanProfitBulanan::findOrFail($id);

        if ($laporan->status === LaporanProfitBulanan::STATUS_LUNAS) {
            throw ValidationException::withMessages([
                'laporan' => 'Laporan yang telah berstatus LUNAS tidak dapat ditandai perlu koreksi.',
            ]);
        }

        return DB::transaction(function () use ($laporan, $admin, $alasan) {
            $laporan->update([
                'perlu_koreksi'   => true,
                'catatan_koreksi' => trim($alasan),
            ]);

            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'perlu_koreksi',
                deskripsi: "Admin {$admin->name} menandai laporan profit {$laporan->nomor} Perlu Koreksi. Catatan: {$alasan}",
                subject: $laporan,
                user: $admin
            );

            $laporan->user?->notify(new MasterFeeCorrectionNotification($laporan, $alasan));

            return $laporan->fresh();
        });
    }

    /**
     * Pagination data laporan untuk portal member.
     */
    public function paginateForMember(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = LaporanProfitBulanan::query()
            ->with('akun')
            ->where('user_id', $user->id);

        if (!empty($filters['status'])) {
            $st = $filters['status'];
            if ($st === 'perlu_koreksi') {
                $query->where('perlu_koreksi', true)->where('status', '!=', LaporanProfitBulanan::STATUS_LUNAS);
            } elseif ($st === 'lunas') {
                $query->where('status', LaporanProfitBulanan::STATUS_LUNAS);
            } elseif ($st === 'tanpa_fee') {
                $query->where('status', LaporanProfitBulanan::STATUS_TANPA_FEE);
            } elseif ($st === 'menunggu_verifikasi') {
                $query->where('status', LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI)->where('perlu_koreksi', false);
            } elseif ($st === 'ditolak') {
                $query->where('status', LaporanProfitBulanan::STATUS_DITOLAK)->where('perlu_koreksi', false);
            } elseif ($st === 'belum_dibayar') {
                $query->where('status', LaporanProfitBulanan::STATUS_BELUM_DIBAYAR)->where('perlu_koreksi', false);
            }
        }

        if (!empty($filters['periode'])) {
            $query->where('periode_akhir', 'like', $filters['periode'] . '%');
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhereHas('akun', function ($aq) use ($search) {
                        $aq->where('nomor_akun_trading', 'like', "%{$search}%")
                            ->orWhere('broker', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('periode_akhir')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Pagination data laporan untuk portal admin.
     */
    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = LaporanProfitBulanan::query()
            ->with(['akun', 'user', 'verifier']);

        if (!empty($filters['status'])) {
            $st = $filters['status'];
            if ($st === 'perlu_koreksi') {
                $query->where('perlu_koreksi', true)->where('status', '!=', LaporanProfitBulanan::STATUS_LUNAS);
            } elseif ($st === 'lunas') {
                $query->where('status', LaporanProfitBulanan::STATUS_LUNAS);
            } elseif ($st === 'tanpa_fee') {
                $query->where('status', LaporanProfitBulanan::STATUS_TANPA_FEE);
            } elseif ($st === 'menunggu_verifikasi') {
                $query->where('status', LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI)->where('perlu_koreksi', false);
            } elseif ($st === 'ditolak') {
                $query->where('status', LaporanProfitBulanan::STATUS_DITOLAK)->where('perlu_koreksi', false);
            } elseif ($st === 'belum_dibayar') {
                $query->where('status', LaporanProfitBulanan::STATUS_BELUM_DIBAYAR)->where('perlu_koreksi', false);
            }
        }

        if (!empty($filters['periode'])) {
            $query->where('periode_akhir', 'like', $filters['periode'] . '%');
        }

        if (isset($filters['perlu_koreksi']) && $filters['perlu_koreksi'] !== '') {
            $query->where('perlu_koreksi', (bool) $filters['perlu_koreksi']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    })
                    ->orWhereHas('akun', function ($aq) use ($search) {
                        $aq->where('nomor_akun_trading', 'like', "%{$search}%")
                            ->orWhere('broker', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('periode_akhir')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'laporan_page')
            ->withQueryString();
    }

    /**
     * Ringkasan performa Master Fee & laporan profit untuk admin.
     */
    public function summary(array|string|null $filters = null): array
    {
        $query = LaporanProfitBulanan::query();
        $periodeParam = null;

        if (is_string($filters)) {
            $query->where('periode_akhir', 'like', $filters . '%');
            $periodeParam = $filters;
        } elseif (is_array($filters)) {
            if (!empty($filters['periode'])) {
                $query->where('periode_akhir', 'like', $filters['periode'] . '%');
                $periodeParam = $filters['periode'];
            }
        }

        $all = $query->get();

        $totalFeeUsd = (float) $all->sum('fee_usd');
        $totalProfitUsd = (float) $all->sum('profit_usd');
        $totalCount = $all->count();

        $lunas = $all->where('status', LaporanProfitBulanan::STATUS_LUNAS);
        $tanpaFee = $all->where('status', LaporanProfitBulanan::STATUS_TANPA_FEE);
        $menunggu = $all->where('status', LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI);
        $menungguBayar = $all->filter(function ($r) {
            return in_array($r->status, [
                LaporanProfitBulanan::STATUS_BELUM_DIBAYAR,
                LaporanProfitBulanan::STATUS_DITOLAK,
            ], true);
        });
        $ditolak = $all->where('status', LaporanProfitBulanan::STATUS_DITOLAK);
        $perluKoreksi = $all->where('perlu_koreksi', true);

        // Belum Lapor: akun aktif yang memiliki periode terbuka tanpa laporan
        $today = Carbon::now();
        $activeAccounts = AkunCopytrade::query()->where('status', 'active')->get();
        $belumLaporCount = 0;
        foreach ($activeAccounts as $acc) {
            if (ProfitPeriod::nextReportablePeriod($acc, $today) !== null) {
                $belumLaporCount++;
            }
        }

        return [
            'total_tagihan_usd'         => round($totalFeeUsd, 2),
            'total_profit_usd'          => round($totalProfitUsd, 2),
            'total_tagihan_count'       => $totalCount,
            'lunas_usd'                 => round((float) $lunas->sum('fee_usd'), 2),
            'lunas_count'               => $lunas->count() + $tanpaFee->count(),
            'menunggu_verifikasi_usd'   => round((float) $menunggu->sum('fee_usd'), 2),
            'menunggu_verifikasi_count' => $menunggu->count(),
            'belum_dibayar_usd'         => round((float) $menungguBayar->sum('fee_usd'), 2),
            'belum_dibayar_count'       => $menungguBayar->count(),
            'ditolak_usd'               => round((float) $ditolak->sum('fee_usd'), 2),
            'ditolak_count'             => $ditolak->count(),
            'perlu_koreksi_count'       => $perluKoreksi->count(),
            'belum_rekap_count'         => $belumLaporCount,
        ];
    }

    /**
     * Daftar akun copytrade aktif yang memiliki periode laporan terbuka namun belum diinput.
     */
    public function missingReports(?string $periode = null, array $filters = []): LengthAwarePaginator
    {
        $today = Carbon::now();
        $activeAccounts = AkunCopytrade::query()
            ->with('user')
            ->where('status', 'active');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $activeAccounts->where(function ($q) use ($search) {
                $q->where('nomor_akun_trading', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        $allActive = $activeAccounts->get();
        $missing = [];

        foreach ($allActive as $akun) {
            $next = ProfitPeriod::nextReportablePeriod($akun, $today);
            if ($next !== null) {
                $akun->next_periode_ke = $next['ke'];
                $akun->next_periode_awal = $next['awal']->toDateString();
                $akun->next_periode_akhir = $next['akhir']->toDateString();
                $akun->next_open_date = $next['open_date']->toDateString();
                $akun->next_periode_label = $next['awal']->translatedFormat('d M Y') . ' - ' . $next['akhir']->translatedFormat('d M Y');
                $missing[] = $akun;
            }
        }

        $page = (int) ($filters['missing_page'] ?? request()->get('missing_page', 1));
        $perPage = 15;
        $total = count($missing);
        $sliced = array_slice($missing, ($page - 1) * $perPage, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            $total,
            $perPage,
            $page,
            [
                'path'     => request()->url(),
                'pageName' => 'missing_page',
                'query'    => request()->query(),
            ]
        );
    }

    /**
     * Mengambil laporan bulanan downline afiliator.
     * STRICT PRIVACY: hanya profit_usd, status_tampil ("Lunas"/"Belum Dibayar"), myfxbook_url, nomor akun.
     * TANPA Rupiah, fee, kurs, atau berkas screenshot.
     */
    public function downlineReport(User $affiliator, array $filters = []): LengthAwarePaginator
    {
        $query = AkunCopytrade::query()
            ->with(['laporanProfit' => fn ($q) => $q->orderByDesc('periode_ke')])
            ->whereHas('user.relasiUpline', fn ($uq) => $uq->where('affiliator_id', $affiliator->id))
            ->where('status', 'active');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nomor_akun_trading', 'like', "%{$search}%")
                    ->orWhere('broker', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('nomor_akun_trading')
            ->paginate(15)
            ->withQueryString()
            ->through(function (AkunCopytrade $akun) {
                return [
                    'id'                 => $akun->id,
                    'nomor_akun_trading' => $akun->nomor_akun_trading,
                    'broker'             => $akun->broker,
                    'tier'               => $akun->tier,
                    'modal_riil'         => (float) $akun->modal_riil,
                    'tanggal_mulai'      => $akun->tanggal_mulai?->toDateString(),
                    'myfxbook_url'       => $akun->myfxbook_url,
                    'laporan'            => $akun->laporanProfit->map(fn (LaporanProfitBulanan $l) => [
                        'id'            => $l->id,
                        'periode_ke'    => $l->periode_ke,
                        'periode_awal'  => $l->periode_awal->toDateString(),
                        'periode_akhir' => $l->periode_akhir->toDateString(),
                        'profit_usd'    => (float) $l->profit_usd,
                        'status_tampil' => $l->status_tampil,
                    ])->all(),
                ];
            });
    }

    /**
     * Mengambil pengaturan Master Fee global.
     */
    public function getSettings(): PengaturanMasterFee
    {
        return PengaturanMasterFee::current();
    }

    /**
     * Memperbarui pengaturan Master Fee global.
     */
    public function updateSettings(array $data, User $admin): PengaturanMasterFee
    {
        $settings = PengaturanMasterFee::current();
        $sebelum = $settings->toArray();

        $settings->update([
            'nama_bank'      => $data['nama_bank'] ?? $settings->nama_bank,
            'nomor_rekening' => $data['nomor_rekening'] ?? $settings->nomor_rekening,
            'atas_nama'      => $data['atas_nama'] ?? $settings->atas_nama,
            'fee_persen'     => isset($data['fee_persen']) ? (int) $data['fee_persen'] : $settings->fee_persen,
            'wd_maks_persen' => isset($data['wd_maks_persen']) ? (int) $data['wd_maks_persen'] : ($settings->wd_maks_persen ?? 60),
        ]);

        $this->auditLogService->record(
            kategori: 'master_fee',
            aksi: 'ubah_pengaturan',
            deskripsi: "Admin {$admin->name} memperbarui pengaturan Master Fee (fee: {$settings->fee_persen}%, max WD: {$settings->wd_maks_persen}%).",
            subject: $settings,
            perubahan: [
                'sebelum' => $sebelum,
                'sesudah' => $settings->fresh()->toArray(),
            ],
            user: $admin
        );

        return $settings;
    }
}
