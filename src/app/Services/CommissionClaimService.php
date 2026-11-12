<?php

namespace App\Services;

use App\Models\KlaimKomisi;
use App\Models\KlaimKomisiItem;
use App\Models\LaporanProfitBulanan;
use App\Models\RelasiReferral;
use App\Models\User;
use App\Notifications\CommissionClaimProcessedNotification;
use App\Notifications\CommissionClaimSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CommissionClaimService
{
    public function __construct(
        private readonly ?ExchangeRateService $exchangeRateService = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {}

    private function getAuditLogService(): AuditLogService
    {
        return $this->auditLogService ?? app(AuditLogService::class);
    }

    /**
     * Ambil informasi kurs USD ke IDR dari ExchangeRateService.
     */
    public function getExchangeRateInfo(): array
    {
        $service = $this->exchangeRateService ?? app(ExchangeRateService::class);

        return $service->getUsdToIdrRate();
    }

    /**
     * Ambil nilai numerik kurs USD ke IDR saat ini.
     */
    public function getUsdToIdrRate(): float
    {
        $rateData = $this->getExchangeRateInfo();

        return (float) ($rateData['rate'] ?? ExchangeRateService::DEFAULT_FALLBACK_RATE);
    }

    /**
     * Dapatkan ringkasan statistik antrean pencairan komisi untuk Administrator.
     *
     * @return array{all: int, pending: int, approved: int, rejected: int, total_transferred: float}
     */
    public function getAdminCommissionStats(): array
    {
        return [
            'all'               => KlaimKomisi::count(),
            'pending'           => KlaimKomisi::where('status', 'pending')->count(),
            'approved'          => KlaimKomisi::where('status', 'approved_transferred')->count(),
            'rejected'          => KlaimKomisi::where('status', 'rejected')->count(),
            'total_transferred' => (float) KlaimKomisi::where('status', 'approved_transferred')->sum('total_nominal_transfer'),
        ];
    }

    /**
     * Dapatkan data rekening bank default affiliator dari riwayat klaim terakhir atau akun copytrade.
     *
     * @return array{nama_bank: string, nomor_rekening: string, atas_nama_rekening: string}
     */
    public function getDefaultBankForAffiliator(User $affiliator): array
    {
        $latestAccount = $affiliator->akunCopytrades()->latest()->first();
        $latestClaim = KlaimKomisi::where('affiliator_id', $affiliator->id)->latest()->first();

        return [
            'nama_bank'          => $latestClaim?->nama_bank ?? $latestAccount?->nama_bank ?? '',
            'nomor_rekening'     => $latestClaim?->nomor_rekening ?? $latestAccount?->nomor_rekening ?? '',
            'atas_nama_rekening' => $latestClaim?->atas_nama_rekening ?? $latestAccount?->atas_nama_rekening ?? $affiliator->name,
        ];
    }

    /**
     * Temukan entitas klaim komisi beserta relasi eager loading atau gagal dengan exception 404.
     */
    public function findClaimOrFail(int $id): KlaimKomisi
    {
        return KlaimKomisi::with(['affiliator', 'items.downline'])->findOrFail($id);
    }

    /**
     * Tentukan skema 4-tiering komisi berdasarkan modal deposit investor (dalam IDR).
     *
     * @return array{tier_level: int, persentase_tier: int, label: string}
     */
    public function determineTier(float $modalIdr): array
    {
        return KlaimKomisiItem::determineTier($modalIdr);
    }

    /**
     * Tentukan skema 4-tiering komisi dari nominal USD dengan konversi ke IDR via ExchangeRateService.
     *
     * @return array{tier_level: int, persentase_tier: int, label: string}
     */
    public function determineTierFromUsd(float $modalUsd, ?float $kursUsdIdr = null): array
    {
        $rate = $kursUsdIdr ?? $this->getUsdToIdrRate();
        $modalIdr = round($modalUsd * $rate, 2);

        return KlaimKomisiItem::determineTier($modalIdr);
    }

    /**
     * Hitung formula bagi hasil dan komisi affiliator berbasis persentase ROI Master Trader.
     *
     * @return array{
     *     gross_profit: float,
     *     master_gross_share: float,
     *     affiliate_commission: float,
     *     master_net_share: float,
     *     investor_net_profit: float,
     *     is_drawdown: bool
     * }
     */
    public function calculateCommission(
        float $depositAmount,
        float $roiPercentage,
        float $masterSharePct = 30.0,
        float $affiliateSharePct = 20.0
    ): array {
        return KlaimKomisiItem::calculateCommission(
            $depositAmount,
            $roiPercentage,
            $masterSharePct,
            $affiliateSharePct
        );
    }

    /**
     * Dapatkan ringkasan statistik komisi affiliator.
     */
    public function getAffiliatorCommissionStats(User $affiliator): array
    {
        $eligibleDownlines = $this->getEligibleDownlinesForCommission($affiliator);

        $downlineSiapCount = 0;
        foreach ($eligibleDownlines as $d) {
            if ($d['is_eligible']) {
                $downlineSiapCount++;
            }
        }

        $totalDicairkan = (float) KlaimKomisi::where('affiliator_id', $affiliator->id)
            ->where('status', 'approved_transferred')
            ->sum('total_nominal_transfer');

        $totalDicairkanIdr = (float) KlaimKomisi::where('affiliator_id', $affiliator->id)
            ->where('status', 'approved_transferred')
            ->sum('total_nominal_transfer_idr');

        $menungguTransfer = KlaimKomisi::where('affiliator_id', $affiliator->id)
            ->where('status', 'pending')
            ->count();

        $klaimDisetujui = KlaimKomisi::where('affiliator_id', $affiliator->id)
            ->where('status', 'approved_transferred')
            ->count();

        return [
            'total_dicairkan'       => $totalDicairkan,
            'total_dicairkan_idr'   => $totalDicairkanIdr,
            'menunggu_transfer'     => $menungguTransfer,
            'klaim_disetujui'       => $klaimDisetujui,
            'downline_siap_diklaim' => $downlineSiapCount,
        ];
    }

    /**
     * Ambil daftar seluruh downline affiliator beserta rincian akun copytrade dan perhitungan hari kehitung.
     * Acuan klaim: 1x klaim per downline dengan periode 3 bulan kalender (tanggal yang sama, misal 1 Apr - 1 Jul)
     * dari tanggal copytrade pertama, serta konversi modal USD ke IDR untuk penentuan Tier.
     */
    public function getEligibleDownlinesForCommission(User $affiliator): array
    {
        $kursUsdIdr = $this->getUsdToIdrRate();

        $relations = RelasiReferral::where('affiliator_id', $affiliator->id)
            ->with(['downline.akunCopytrades' => function ($q) {
                $q->where('status', 'active')->orderBy('tanggal_mulai', 'asc');
            }])
            ->get();

        $downlineIds = $relations->pluck('downline_id')->filter()->unique()->all();
        $existingClaimItems = KlaimKomisiItem::whereIn('downline_id', $downlineIds)
            ->with('klaimKomisi')
            ->get()
            ->groupBy('downline_id');

        $result = [];

        foreach ($relations as $relasi) {
            $downline = $relasi->downline;
            if (!$downline) {
                continue;
            }

            $activeAccounts = $downline->akunCopytrades
                ->where('status', 'active')
                ->sortBy(fn ($a) => $a->tanggal_mulai)
                ->values();

            $accountCount = $activeAccounts->count();
            $hasMultiCopytrade = $accountCount > 1;

            $existingItems = $existingClaimItems->get($downline->id, collect());
            $activeExisting = $existingItems->first(function ($it) {
                return $it->klaimKomisi && in_array($it->klaimKomisi->status, ['pending', 'approved_transferred'], true);
            });

            $isClaimed = false;
            $isPending = false;
            if ($activeExisting) {
                if ($activeExisting->klaimKomisi->status === 'approved_transferred') {
                    $isClaimed = true;
                } elseif ($activeExisting->klaimKomisi->status === 'pending') {
                    $isPending = true;
                }
            }

            $firstStartedAt = null;
            $activeDays = 0;
            $targetDays = 90;
            $periodStartDate = null;
            $periodEndDate = null;
            $totalModalRiil = 0.0;
            $totalModalIdr = 0.0;
            $tierInfo = ['tier_level' => 4, 'persentase_tier' => 20, 'label' => 'Tier 4 (< Rp 100 Juta) - Komisi 20%'];
            $accountsDetail = [];
            $downlineHasUnsettled = false;
            $downlineMasterGrossShare = 0.0;
            $downlineAffiliateCommission = 0.0;
            $downlineGrossProfit = 0.0;

            if ($accountCount > 0) {
                foreach ($activeAccounts as $index => $acc) {
                    $isFirst = ($index === 0);
                    $modalUsd = (float) $acc->modal_riil;
                    $modalIdr = round($modalUsd * $kursUsdIdr, 2);
                    $accTierInfo = KlaimKomisiItem::determineTier($modalIdr);

                    // Ambil 3 laporan profit bulanan pertama akun copytrade ini
                    $reports = LaporanProfitBulanan::query()
                        ->where('akun_copytrade_id', $acc->id)
                        ->whereIn('periode_ke', [1, 2, 3])
                        ->orderBy('periode_ke', 'asc')
                        ->get();

                    $reportsCount = $reports->count();

                    // Periksa apakah ada laporan pada 3 periode pertama yang belum lunas
                    $accHasUnsettled = $reports->contains(fn ($r) => in_array($r->status, [
                        LaporanProfitBulanan::STATUS_BELUM_DIBAYAR,
                        LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI,
                        LaporanProfitBulanan::STATUS_DITOLAK,
                    ], true));

                    if ($accHasUnsettled) {
                        $downlineHasUnsettled = true;
                    }

                    $accMasterGrossShare = (float) $reports->sum('fee_usd');
                    $accGrossProfit = (float) $reports->sum('profit_usd');
                    $accMasterGrossShareIdr = (int) $reports->sum('fee_idr');
                    $accAffiliateCommissionIdr = (int) round($accMasterGrossShareIdr * ((float) $accTierInfo['persentase_tier'] / 100));

                    $accCalc = KlaimKomisiItem::calculateCommissionFromPaidFee(
                        $accMasterGrossShare,
                        $accGrossProfit,
                        (float) $accTierInfo['persentase_tier'],
                        $modalUsd
                    );

                    $downlineMasterGrossShare += $accCalc['master_gross_share'];
                    $downlineAffiliateCommission += $accCalc['affiliate_commission'];
                    $downlineGrossProfit += $accCalc['gross_profit'];

                    $accStart = $reports->first()?->periode_awal ? Carbon::parse($reports->first()->periode_awal)->startOfDay() : ($acc->tanggal_mulai ? Carbon::parse($acc->tanggal_mulai)->startOfDay() : null);
                    $accEnd = $reports->count() >= 3 ? Carbon::parse($reports->get(2)->periode_akhir)->startOfDay() : ($reports->last()?->periode_akhir ? Carbon::parse($reports->last()->periode_akhir)->startOfDay() : null);
                    $hariKehitung = ($accStart && $accEnd) ? (int) $accStart->diffInDays($accEnd) : 90;

                    $accountsDetail[] = [
                        'id'                       => $acc->id,
                        'broker'                   => $acc->broker,
                        'nomor_akun'               => $acc->nomor_akun_trading,
                        'tipe_akun'                => $acc->tipe_akun,
                        'tier'                     => $acc->tier,
                        'modal_riil'               => $modalUsd,
                        'modal_idr'                => $modalIdr,
                        'tier_level'               => $accTierInfo['tier_level'],
                        'persentase_tier'          => $accTierInfo['persentase_tier'],
                        'tier_info'                => $accTierInfo,
                        'is_first'                 => $isFirst,
                        'tanggal_mulai'            => $accStart ? (function_exists('tanggal_indo') ? tanggal_indo($accStart) : $accStart->format('d M Y')) : null,
                        'tanggal_selesai'          => $accEnd ? (function_exists('tanggal_indo') ? tanggal_indo($accEnd) : $accEnd->format('d M Y')) : null,
                        'tanggal_mulai_raw'        => $accStart?->toDateString(),
                        'tanggal_selesai_raw'      => $accEnd?->toDateString(),
                        'selisih_hari'             => 0,
                        'hari_kehitung'            => $hariKehitung,
                        'reports_count'            => $reportsCount,
                        'reports_list'             => $reports,
                        'master_gross_share'       => $accCalc['master_gross_share'],
                        'master_gross_share_idr'   => $accMasterGrossShareIdr,
                        'affiliate_commission'     => $accCalc['affiliate_commission'],
                        'affiliate_commission_idr' => $accAffiliateCommissionIdr,
                        'gross_profit'             => $accCalc['gross_profit'],
                        'roi_percentage'           => $accCalc['roi_percentage'],
                        'is_drawdown'              => $accCalc['is_drawdown'],
                        'has_unsettled_fee'        => $accHasUnsettled,
                    ];
                }

                $accountCount = count($accountsDetail);
                $hasMultiCopytrade = $accountCount > 1;
                $totalModalRiil = (float) collect($accountsDetail)->sum('modal_riil');
                $totalModalIdr = round($totalModalRiil * $kursUsdIdr, 2);
                $tierInfo = !empty($accountsDetail)
                    ? $accountsDetail[0]['tier_info']
                    : ['tier_level' => 4, 'persentase_tier' => 20, 'label' => 'Tier 4 (< Rp 100 Juta) - Komisi 20%'];

                if (!empty($accountsDetail)) {
                    $firstStartedAt = $accountsDetail[0]['tanggal_mulai_raw'];
                    $periodStartDate = $accountsDetail[0]['tanggal_mulai_raw'] ? Carbon::parse($accountsDetail[0]['tanggal_mulai_raw']) : null;
                    $periodEndDate = $accountsDetail[0]['tanggal_selesai_raw'] ? Carbon::parse($accountsDetail[0]['tanggal_selesai_raw']) : null;
                    $activeDays = $accountsDetail[0]['hari_kehitung'];
                    $targetDays = $accountsDetail[0]['hari_kehitung'];
                }
            }

            // Hitung status stepper 3 periode untuk downline
            $periodsProgress = [];
            for ($p = 1; $p <= 3; $p++) {
                if ($accountCount === 0) {
                    $periodsProgress[] = [
                        'periode_ke'    => $p,
                        'label'         => "P{$p}",
                        'state'         => 'waiting_report',
                        'status_tampil' => 'Belum Lapor',
                        'periode_label' => null,
                    ];
                    continue;
                }

                $reportsForP = collect();
                foreach ($accountsDetail as $ad) {
                    $r = $ad['reports_list']->firstWhere('periode_ke', $p);
                    if ($r) {
                        $reportsForP->push($r);
                    }
                }

                if ($reportsForP->count() < $accountCount) {
                    $firstRep = $reportsForP->first();
                    $dateRange = $firstRep ? Carbon::parse($firstRep->periode_awal)->translatedFormat('d M') . ' – ' . Carbon::parse($firstRep->periode_akhir)->translatedFormat('d M Y') : null;
                    $periodsProgress[] = [
                        'periode_ke'    => $p,
                        'label'         => "P{$p}",
                        'state'         => 'waiting_report',
                        'status_tampil' => 'Belum Lapor',
                        'periode_label' => $dateRange,
                    ];
                } else {
                    $allSettled = $reportsForP->every(fn ($r) => in_array($r->status, [
                        LaporanProfitBulanan::STATUS_LUNAS,
                        LaporanProfitBulanan::STATUS_TANPA_FEE,
                    ], true));

                    $firstRep = $reportsForP->first();
                    $dateRange = $firstRep ? Carbon::parse($firstRep->periode_awal)->translatedFormat('d M') . ' – ' . Carbon::parse($firstRep->periode_akhir)->translatedFormat('d M Y') : null;

                    if ($allSettled) {
                        $periodsProgress[] = [
                            'periode_ke'    => $p,
                            'label'         => "P{$p}",
                            'state'         => 'settled',
                            'status_tampil' => $firstRep?->status === LaporanProfitBulanan::STATUS_TANPA_FEE ? 'Tanpa Fee' : 'Lunas',
                            'periode_label' => $dateRange,
                        ];
                    } else {
                        $periodsProgress[] = [
                            'periode_ke'    => $p,
                            'label'         => "P{$p}",
                            'state'         => 'unsettled',
                            'status_tampil' => $firstRep?->status_tampil ?? 'Menunggu',
                            'periode_label' => $dateRange,
                        ];
                    }
                }
            }

            $completedPeriodsCount = collect($periodsProgress)->where('state', 'settled')->count();

            // Penentuan kelayakan: wajib memiliki 3 laporan bulanan pertama, bebas tagihan belum bayar/ditolak/menunggu verifikasi, dan belum pernah diklaim
            $isEligible = false;
            $statusLabel = 'Belum Copytrade';
            $reason = 'Belum memiliki akun copytrade aktif.';

            if ($isClaimed) {
                $isEligible = false;
                $statusLabel = 'Sudah Dicairkan';
                $reason = 'Hak komisi 3 periode laporan untuk investor ini telah dicairkan.';
            } elseif ($isPending) {
                $isEligible = false;
                $statusLabel = 'Menunggu Verifikasi';
                $reason = 'Permohonan klaim untuk investor ini sedang ditinjau Administrator.';
            } elseif ($accountCount > 0) {
                $allAccountsHave3Reports = collect($accountsDetail)->every(fn ($a) => ($a['reports_count'] ?? 0) >= 3);
                $minReports = collect($accountsDetail)->min('reports_count') ?? 0;

                if (!$allAccountsHave3Reports) {
                    $isEligible = false;
                    $remaining = 3 - $minReports;
                    $statusLabel = "Laporan {$minReports}/3 Selesai";
                    $reason = "Baru {$minReports} dari 3 laporan profit bulanan pertama yang diterbitkan untuk akun investor.";
                } elseif ($downlineHasUnsettled) {
                    $isEligible = false;
                    $statusLabel = 'Tagihan Belum Selesai';
                    $reason = 'Terdapat tagihan Master Fee investor yang belum lunas (belum dibayar, ditolak, atau menunggu verifikasi). Semua tagihan pada 3 periode pertama wajib lunas terlebih dahulu.';
                } else {
                    $isEligible = true;
                    $statusLabel = 'Siap Diklaim (3 Periode)';
                    $reason = 'Seluruh 3 periode laporan bulanan dan pembayaran Master Fee telah selesai dan memenuhi syarat pencairan komisi.';
                }
            }

            $accountsFormatted = array_map(function ($acc) {
                unset($acc['reports_list']);

                return $acc;
            }, $accountsDetail);

            $result[] = [
                'id'                             => $downline->id,
                'name'                           => $downline->name,
                'username'                       => $downline->username,
                'email'                          => $downline->email,
                'phone'                          => $downline->phone,
                'first_started_at'               => $periodStartDate ? tanggal_indo($periodStartDate) : null,
                'first_started_at_raw'           => $periodStartDate ? $periodStartDate->toDateString() : null,
                'period_end_date'                => $periodEndDate ? tanggal_indo($periodEndDate) : null,
                'active_days'                    => $activeDays,
                'target_days'                    => $targetDays,
                'periods_progress'               => $periodsProgress,
                'completed_periods_count'        => $completedPeriodsCount,
                'jumlah_akun'                    => $accountCount,
                'has_multi_copytrade'            => $hasMultiCopytrade,
                'total_modal_riil'               => $totalModalRiil,
                'total_modal_idr'                => $totalModalIdr,
                'total_gross_profit'             => round($downlineGrossProfit, 2),
                'total_master_gross_share'       => round($downlineMasterGrossShare, 2),
                'total_master_gross_share_idr'   => (int) collect($accountsDetail)->sum('master_gross_share_idr'),
                'total_affiliate_commission'     => round($downlineAffiliateCommission, 2),
                'total_affiliate_commission_idr' => (int) collect($accountsDetail)->sum('affiliate_commission_idr'),
                'has_unsettled_fee'              => $downlineHasUnsettled,
                'kurs_usd_idr'                   => $kursUsdIdr,
                'tier_info'                      => $tierInfo,
                'accounts'                       => $accountsFormatted,
                'is_eligible'                    => $isEligible,
                'is_claimed'                     => $isClaimed,
                'is_pending'                     => $isPending,
                'status_label'                   => $statusLabel,
                'reason'                         => $reason,
            ];
        }

        // Urutkan: paling atas yang belum ada copytrade (null), lalu dari tanggal mulai copytrade paling awal (ASC)
        usort($result, function (array $a, array $b): int {
            $dateA = $a['first_started_at_raw'] ?? null;
            $dateB = $b['first_started_at_raw'] ?? null;

            if ($dateA === null && $dateB !== null) {
                return -1;
            }

            if ($dateA !== null && $dateB === null) {
                return 1;
            }

            if ($dateA !== null && $dateB !== null && $dateA !== $dateB) {
                return strcmp($dateA, $dateB);
            }

            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        });

        return $result;
    }

    /**
     * Simpan pengajuan klaim komisi batch (bisa memilih banyak downline yang memenuhi syarat 3 bulan sekaligus).
     * Seluruh downline dan akun copytrade disimpan sebagai 1 (SATU) baris tiket klaim komisi.
     *
     * @throws ValidationException
     */
    public function submitBatchClaims(User $affiliator, array $data): KlaimKomisi
    {
        $downlineIds = $data['downline_ids'] ?? [];
        if (empty($downlineIds) || !is_array($downlineIds)) {
            throw ValidationException::withMessages([
                'downline_ids' => 'Pilih minimal satu investor downline yang telah aktif minimal 3 bulan.',
            ]);
        }

        $namaBank = trim((string) ($data['nama_bank'] ?? ''));
        $nomorRekening = trim((string) ($data['nomor_rekening'] ?? ''));
        $atasNama = trim((string) ($data['atas_nama_rekening'] ?? ''));

        if (empty($namaBank) || empty($nomorRekening) || empty($atasNama)) {
            throw ValidationException::withMessages([
                'nama_bank' => 'Data rekening bank tujuan transfer wajib diisi lengkap.',
            ]);
        }

        $kursUsdIdr = $this->getUsdToIdrRate();

        return DB::transaction(function () use ($affiliator, $downlineIds, $namaBank, $nomorRekening, $atasNama, $kursUsdIdr) {
            $preparedItems = [];
            $uniqueDownlineIds = array_values(array_unique(array_map('intval', $downlineIds)));

            foreach ($uniqueDownlineIds as $downlineId) {
                // 1. Verifikasi relasi referral sah
                $isDownline = RelasiReferral::where('affiliator_id', $affiliator->id)
                    ->where('downline_id', $downlineId)
                    ->exists();

                if (!$isDownline) {
                    throw ValidationException::withMessages([
                        'downline_ids' => "Investor ID {$downlineId} bukan merupakan downline sah dari akun Anda.",
                    ]);
                }

                // 2. Ambil seluruh akun copytrade aktif downline
                $downline = User::with(['akunCopytrades' => function ($q) {
                    $q->where('status', 'active')->orderBy('tanggal_mulai', 'asc');
                }])->findOrFail($downlineId);

                $activeAccounts = $downline->akunCopytrades
                    ->where('status', 'active')
                    ->sortBy(fn ($a) => $a->tanggal_mulai)
                    ->values();

                if ($activeAccounts->isEmpty()) {
                    throw ValidationException::withMessages([
                        'downline_ids' => "Investor downline {$downline->name} belum memiliki akun copytrade yang aktif.",
                    ]);
                }

                // 3. Verifikasi ketersediaan 3 laporan bulanan pertama per akun aktif & cek tagihan belum lunas
                foreach ($activeAccounts as $acc) {
                    $reports = LaporanProfitBulanan::query()
                        ->where('akun_copytrade_id', $acc->id)
                        ->whereIn('periode_ke', [1, 2, 3])
                        ->orderBy('periode_ke', 'asc')
                        ->get();

                    if ($reports->count() < 3) {
                        throw ValidationException::withMessages([
                            'downline_ids' => "Investor {$downline->name} baru memiliki {$reports->count()}/3 laporan bulanan. Klaim komisi mensyaratkan 3 laporan profit bulanan pertama telah terbit.",
                        ]);
                    }

                    $hasUnsettled = $reports->contains(fn ($r) => in_array($r->status, [
                        LaporanProfitBulanan::STATUS_BELUM_DIBAYAR,
                        LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI,
                        LaporanProfitBulanan::STATUS_DITOLAK,
                    ], true));

                    if ($hasUnsettled) {
                        throw ValidationException::withMessages([
                            'downline_ids' => "Investor {$downline->name} memiliki tagihan Master Fee yang belum selesai pada 3 periode pertama (belum dibayar, ditolak, atau menunggu verifikasi). Semua tagihan wajib diselesaikan terlebih dahulu.",
                        ]);
                    }
                }

                // 4. Verifikasi klaim 1x saja per downline: tidak boleh ada yang sedang pending atau approved
                $alreadyClaimed = KlaimKomisiItem::where('downline_id', $downlineId)
                    ->whereHas('klaimKomisi', fn ($q) => $q->whereIn('status', ['pending', 'approved_transferred']))
                    ->exists();

                if ($alreadyClaimed) {
                    throw ValidationException::withMessages([
                        'downline_ids' => "Investor {$downline->name} sudah pernah diajukan klaim komisi atau sedang dalam peninjauan.",
                    ]);
                }

                // 5. Siapkan record item per akun copytrade dari 3 laporan bulanan pertama
                foreach ($activeAccounts as $index => $acc) {
                    $reports = LaporanProfitBulanan::query()
                        ->where('akun_copytrade_id', $acc->id)
                        ->whereIn('periode_ke', [1, 2, 3])
                        ->orderBy('periode_ke', 'asc')
                        ->get();

                    $modalUsd = (float) $acc->modal_riil;
                    $modalIdr = round($modalUsd * $kursUsdIdr, 2);
                    $accTierInfo = KlaimKomisiItem::determineTier($modalIdr);

                    $masterGrossShare = (float) $reports->sum('fee_usd');
                    $grossProfit = (float) $reports->sum('profit_usd');
                    $masterGrossShareIdr = (int) $reports->sum('fee_idr');
                    $affiliateSharePct = (float) $accTierInfo['persentase_tier'];
                    $affiliateCommissionIdr = (int) round($masterGrossShareIdr * ($affiliateSharePct / 100));

                    $calc = KlaimKomisiItem::calculateCommissionFromPaidFee(
                        $masterGrossShare,
                        $grossProfit,
                        $affiliateSharePct,
                        $modalUsd
                    );

                    $p1 = $reports->first();
                    $p3 = $reports->last();
                    $accStart = $p1 ? Carbon::parse($p1->periode_awal)->startOfDay() : ($acc->tanggal_mulai ? Carbon::parse($acc->tanggal_mulai)->startOfDay() : now());
                    $accEnd = $p3 ? Carbon::parse($p3->periode_akhir)->startOfDay() : $accStart->copy()->addMonthsNoOverflow(3);
                    $hariKehitung = (int) $accStart->diffInDays($accEnd);

                    $preparedItems[] = [
                        'downline_id'              => $downlineId,
                        'akun_copytrade_id'        => $acc->id,
                        'broker'                   => $acc->broker,
                        'nomor_akun_trading'       => $acc->nomor_akun_trading,
                        'is_akun_pertama'          => ($index === 0),
                        'tanggal_mulai'            => $accStart->toDateString(),
                        'tanggal_selesai'          => $accEnd->toDateString(),
                        'hari_kehitung'            => $hariKehitung,
                        'modal_riil'               => $modalUsd,
                        'deposit_amount'           => $modalUsd,
                        'tier_level'               => $accTierInfo['tier_level'],
                        'persentase_tier'          => $accTierInfo['persentase_tier'],
                        'master_share_pct'         => (float) ($p1?->fee_persen ?? 30.00),
                        'affiliate_share_pct'      => $affiliateSharePct,
                        'roi_percentage'           => $calc['roi_percentage'],
                        'gross_profit'             => $calc['gross_profit'],
                        'master_gross_share'       => $calc['master_gross_share'],
                        'master_gross_share_idr'   => $masterGrossShareIdr,
                        'affiliate_commission'     => $calc['affiliate_commission'],
                        'affiliate_commission_idr' => $affiliateCommissionIdr,
                        'master_net_share'         => $calc['master_net_share'],
                        'investor_net_profit'      => $calc['investor_net_profit'],
                        'profit_master'            => $calc['master_gross_share'],
                        'nominal_transfer'         => $calc['affiliate_commission'],
                    ];
                }
            }

            // Simpan sebagai 1 (SATU) row tiket klaim komisi
            $totalModalRiil = collect($preparedItems)->sum('modal_riil');
            $totalGrossProfit = collect($preparedItems)->sum('gross_profit');
            $totalMasterGrossShare = collect($preparedItems)->sum('master_gross_share');
            $totalMasterNetShare = collect($preparedItems)->sum('master_net_share');
            $totalInvestorNetProfit = collect($preparedItems)->sum('investor_net_profit');
            $totalAffiliateCommission = collect($preparedItems)->sum('affiliate_commission');
            $totalNominalTransferIdr = (float) collect($preparedItems)->sum('affiliate_commission_idr');
            $kursKonversiIdr = $totalAffiliateCommission > 0
                ? round($totalNominalTransferIdr / $totalAffiliateCommission, 2)
                : $kursUsdIdr;
            $totalDownlines = count($uniqueDownlineIds);
            $totalAkun = count($preparedItems);

            $claim = KlaimKomisi::create([
                'kode_klaim'                 => KlaimKomisi::generateKodeKlaim(),
                'affiliator_id'              => $affiliator->id,
                'total_downline'             => $totalDownlines,
                'total_akun'                 => $totalAkun,
                'total_modal_riil'           => $totalModalRiil,
                'total_deposit_amount'       => $totalModalRiil,
                'total_gross_profit'         => $totalGrossProfit,
                'total_master_gross_share'   => $totalMasterGrossShare,
                'total_master_net_share'     => $totalMasterNetShare,
                'total_investor_net_profit'  => $totalInvestorNetProfit,
                'total_affiliate_commission' => $totalAffiliateCommission,
                'total_profit_master'        => $totalMasterGrossShare,
                'total_nominal_transfer'     => $totalAffiliateCommission,
                'kurs_konversi_idr'          => $kursKonversiIdr,
                'total_nominal_transfer_idr' => $totalNominalTransferIdr,
                'nama_bank'                  => $namaBank,
                'nomor_rekening'             => $nomorRekening,
                'atas_nama_rekening'         => $atasNama,
                'status'                     => 'pending',
            ]);

            // Simpan seluruh rincian akun copytrade
            foreach ($preparedItems as $itemData) {
                $claim->items()->create($itemData);
            }

            // Kirim notifikasi ke Administrator
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CommissionClaimSubmittedNotification($claim));
            }

            // Audit Log: Pengajuan klaim komisi
            $this->getAuditLogService()->record(
                kategori: 'komisi_afiliasi',
                aksi: 'ajukan_klaim_komisi',
                deskripsi: "Affiliator {$affiliator->name} mengajukan klaim komisi #{$claim->kode_klaim} untuk {$totalDownlines} investor ({$totalAkun} akun trading, total modal: \${$totalModalRiil}).",
                subject: $claim,
                perubahan: [
                    'sesudah' => [
                        'kode_klaim'         => $claim->kode_klaim,
                        'total_downline'     => $totalDownlines,
                        'total_akun'         => $totalAkun,
                        'total_modal_riil'   => $totalModalRiil,
                        'nama_bank'          => $namaBank,
                        'nomor_rekening'     => $nomorRekening,
                        'atas_nama_rekening' => $atasNama,
                    ],
                ],
                tingkat: 'info',
                user: $affiliator
            );

            return $claim;
        });
    }

    /**
     * Kompatibilitas mundur untuk klaim komisi tunggal.
     */
    public function submitClaim(User $affiliator, array $data, ?UploadedFile $buktiModal = null): KlaimKomisi
    {
        $downlineId = (int) ($data['downline_id'] ?? 0);

        return $this->submitBatchClaims($affiliator, [
            'downline_ids'       => [$downlineId],
            'nama_bank'          => $data['nama_bank'] ?? '',
            'nomor_rekening'     => $data['nomor_rekening'] ?? '',
            'atas_nama_rekening' => $data['atas_nama_rekening'] ?? '',
        ]);
    }

    /**
     * Dapatkan daftar klaim komisi terpaginasi dengan filter pencarian dan status.
     * 1 Baris per Tiket Klaim.
     */
    public function getPaginatedClaims(array $filters = [], ?User $affiliator = null, string $pageName = 'page'): LengthAwarePaginator
    {
        $query = KlaimKomisi::with(['affiliator', 'processor', 'items.downline']);

        if ($affiliator !== null) {
            $query->where('affiliator_id', $affiliator->id);
        }

        // Filter status
        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'approved_transferred', 'rejected'], true)) {
            $query->where('status', $filters['status']);
        }

        // Filter pencarian
        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('kode_klaim', 'like', "%{$search}%")
                    ->orWhereHas('affiliator', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.downline', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Dynamic sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $direction = strtolower($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'kode_klaim', 'total_downline', 'total_akun', 'total_modal_riil', 'total_nominal_transfer', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->latest();
        }

        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 10)));
        $kursUsdIdr = $this->getUsdToIdrRate();

        return $query->paginate($perPage, ['*'], $pageName)
            ->withQueryString()
            ->through(function (KlaimKomisi $claim) use ($kursUsdIdr) {
                $items = $claim->items->map(fn (KlaimKomisiItem $it) => [
                    'id'                       => $it->id,
                    'downline_id'              => $it->downline_id,
                    'downline_name'            => $it->downline?->name ?? 'Investor',
                    'downline_username'        => $it->downline?->username,
                    'downline_email'           => $it->downline?->email,
                    'broker'                   => $it->broker,
                    'nomor_akun_trading'       => $it->nomor_akun_trading,
                    'is_akun_pertama'          => (bool) $it->is_akun_pertama,
                    'tanggal_mulai'            => tanggal_indo($it->tanggal_mulai),
                    'tanggal_selesai'          => tanggal_indo($it->tanggal_selesai),
                    'hari_kehitung'            => $it->hari_kehitung,
                    'modal_riil'               => (float) $it->modal_riil,
                    'modal_idr'                => round((float) $it->modal_riil * $kursUsdIdr, 2),
                    'deposit_amount'           => (float) ($it->deposit_amount ?: $it->modal_riil),
                    'tier_level'               => $it->tier_level,
                    'persentase_tier'          => $it->persentase_tier,
                    'roi_percentage'           => $it->roi_percentage !== null ? (float) $it->roi_percentage : null,
                    'gross_profit'             => $it->gross_profit !== null ? (float) $it->gross_profit : null,
                    'master_share_pct'         => $it->master_share_pct !== null ? (float) $it->master_share_pct : 30.0,
                    'master_gross_share'       => $it->master_gross_share !== null ? (float) $it->master_gross_share : null,
                    'master_gross_share_idr'   => (int) ($it->master_gross_share_idr ?? 0),
                    'affiliate_share_pct'      => $it->affiliate_share_pct !== null ? (float) $it->affiliate_share_pct : (float) $it->persentase_tier,
                    'affiliate_commission'     => $it->affiliate_commission !== null ? (float) $it->affiliate_commission : null,
                    'affiliate_commission_idr' => (int) ($it->affiliate_commission_idr ?? 0),
                    'master_net_share'         => $it->master_net_share !== null ? (float) $it->master_net_share : null,
                    'investor_net_profit'      => $it->investor_net_profit !== null ? (float) $it->investor_net_profit : null,
                    'profit_master'            => $it->profit_master !== null ? (float) $it->profit_master : null,
                    'nominal_transfer'         => $it->nominal_transfer !== null ? (float) $it->nominal_transfer : null,
                ]);

                $uniqueDownlines = $items->pluck('downline_name')->filter()->unique()->values();
                $downlinesSummary = $uniqueDownlines->join(', ');

                return [
                    'id'                         => $claim->id,
                    'kode_klaim'                 => $claim->kode_klaim,
                    'total_downline'             => $claim->total_downline,
                    'total_akun'                 => $claim->total_akun,
                    'total_modal_riil'           => (float) $claim->total_modal_riil,
                    'total_modal_idr'            => round((float) $claim->total_modal_riil * $kursUsdIdr, 2),
                    'total_deposit_amount'       => (float) ($claim->total_deposit_amount ?: $claim->total_modal_riil),
                    'total_gross_profit'         => $claim->total_gross_profit !== null ? (float) $claim->total_gross_profit : null,
                    'total_master_gross_share'   => $claim->total_master_gross_share !== null ? (float) $claim->total_master_gross_share : null,
                    'total_master_net_share'     => $claim->total_master_net_share !== null ? (float) $claim->total_master_net_share : null,
                    'total_investor_net_profit'  => $claim->total_investor_net_profit !== null ? (float) $claim->total_investor_net_profit : null,
                    'total_affiliate_commission' => $claim->total_affiliate_commission !== null ? (float) $claim->total_affiliate_commission : null,
                    'total_profit_master'        => $claim->total_profit_master !== null ? (float) $claim->total_profit_master : null,
                    'total_nominal_transfer'     => $claim->total_nominal_transfer !== null ? (float) $claim->total_nominal_transfer : null,
                    'nominal_transfer'           => $claim->total_nominal_transfer !== null ? (float) $claim->total_nominal_transfer : null,
                    'kurs_usd_idr'               => $kursUsdIdr,
                    'kurs_konversi_idr'          => $claim->kurs_konversi_idr !== null ? (float) $claim->kurs_konversi_idr : null,
                    'total_nominal_transfer_idr' => $claim->total_nominal_transfer_idr !== null ? (float) $claim->total_nominal_transfer_idr : null,
                    'nama_bank'                  => $claim->nama_bank,
                    'nomor_rekening'             => $claim->nomor_rekening,
                    'atas_nama_rekening'         => $claim->atas_nama_rekening,
                    'status'                     => $claim->status,
                    'catatan_admin'              => $claim->catatan_admin,
                    'processor'                  => $claim->processor ? [
                        'id'   => $claim->processor->id,
                        'name' => $claim->processor->name,
                    ] : null,
                    'processed_at'       => $claim->processed_at ? tanggal_waktu_indo($claim->processed_at) : null,
                    'created_at'         => tanggal_waktu_indo($claim->created_at),
                    'created_at_raw'     => $claim->created_at?->toIso8601String(),
                    'bukti_transfer_url' => $claim->bukti_transfer_path ? route('documents.commission_receipt', $claim->id, false) : null,
                    'affiliator'         => $claim->affiliator ? [
                        'id'       => $claim->affiliator->id,
                        'name'     => $claim->affiliator->name,
                        'username' => $claim->affiliator->username,
                        'email'    => $claim->affiliator->email,
                        'phone'    => $claim->affiliator->phone,
                    ] : null,
                    'items'             => $items->values()->all(),
                    'summary_downlines' => $downlinesSummary ?: '1 Investor',
                ];
            });
    }

    /**
     * Proses keputusan pencairan dana komisi atau penolakan oleh Administrator.
     * Admin menginput nominal profit Master Trader untuk masing-masing akun copytrade berdasarkan periode tanggalnya
     * serta kurs konversi riil MT4 ke Rupiah.
     *
     * @throws ValidationException
     */
    public function processClaim(
        KlaimKomisi|int $claim,
        string $status,
        ?float $profitMaster,
        ?UploadedFile $buktiTransfer,
        ?string $catatanAdmin,
        User $processor,
        ?array $itemizedProfits = null,
        ?float $kursKonversiIdr = null
    ): KlaimKomisi {
        if (is_int($claim)) {
            $claim = $this->findClaimOrFail($claim);
        }

        if (!in_array($status, ['approved_transferred', 'rejected'], true)) {
            throw new \InvalidArgumentException("Status keputusan tidak valid: {$status}");
        }

        if ($status === 'approved_transferred') {
            if (!$buktiTransfer) {
                throw ValidationException::withMessages([
                    'bukti_transfer' => 'Berkas bukti struk transfer bank wajib diunggah untuk menyelesaikan pencairan.',
                ]);
            }

            $finalKursIdr = ($kursKonversiIdr !== null && $kursKonversiIdr > 0)
                ? $kursKonversiIdr
                : $this->getUsdToIdrRate();

            return DB::transaction(function () use ($claim, $status, $profitMaster, $itemizedProfits, $buktiTransfer, $catatanAdmin, $processor, $finalKursIdr, $kursKonversiIdr) {
                $claim->load('items.downline');
                $items = $claim->items;

                $totalGrossProfit = 0.0;
                $totalMasterGrossShare = 0.0;
                $totalAffiliateCommission = 0.0;
                $totalMasterNetShare = 0.0;
                $totalInvestorNetProfit = 0.0;

                if (!empty($itemizedProfits)) {
                    $profitMap = collect($itemizedProfits)->keyBy('id');

                    foreach ($items as $item) {
                        $input = $profitMap->get($item->id, []);
                        $roiPct = isset($input['roi_percentage'])
                            ? (float) $input['roi_percentage']
                            : (isset($input['profit_master']) ? (float) $input['profit_master'] : (float) $profitMaster);

                        $masterSharePct = isset($input['master_share_pct']) && is_numeric($input['master_share_pct'])
                            ? (float) $input['master_share_pct']
                            : (float) ($item->master_share_pct ?: 30.0);

                        $affiliateSharePct = isset($input['affiliate_share_pct']) && is_numeric($input['affiliate_share_pct'])
                            ? (float) $input['affiliate_share_pct']
                            : (float) ($item->affiliate_share_pct ?: $item->persentase_tier);

                        $depositAmount = (float) ($item->deposit_amount ?: $item->modal_riil);

                        $calc = KlaimKomisiItem::calculateCommission(
                            $depositAmount,
                            $roiPct,
                            $masterSharePct,
                            $affiliateSharePct
                        );

                        $item->update([
                            'roi_percentage'           => $roiPct,
                            'master_share_pct'         => $masterSharePct,
                            'affiliate_share_pct'      => $affiliateSharePct,
                            'gross_profit'             => $calc['gross_profit'],
                            'master_gross_share'       => $calc['master_gross_share'],
                            'master_gross_share_idr'   => (int) round($calc['master_gross_share'] * $finalKursIdr),
                            'affiliate_commission'     => $calc['affiliate_commission'],
                            'affiliate_commission_idr' => (int) round($calc['affiliate_commission'] * $finalKursIdr),
                            'master_net_share'         => $calc['master_net_share'],
                            'investor_net_profit'      => $calc['investor_net_profit'],
                            'profit_master'            => $calc['master_gross_share'],
                            'nominal_transfer'         => $calc['affiliate_commission'],
                        ]);

                        $totalGrossProfit += $calc['gross_profit'];
                        $totalMasterGrossShare += $calc['master_gross_share'];
                        $totalAffiliateCommission += $calc['affiliate_commission'];
                        $totalMasterNetShare += $calc['master_net_share'];
                        $totalInvestorNetProfit += $calc['investor_net_profit'];
                    }
                } elseif ($profitMaster !== null) {
                    $roiPct = (float) $profitMaster;
                    foreach ($items as $item) {
                        $depositAmount = (float) ($item->deposit_amount ?: $item->modal_riil);
                        $masterSharePct = (float) ($item->master_share_pct ?: 30.0);
                        $affiliateSharePct = (float) ($item->affiliate_share_pct ?: $item->persentase_tier);

                        $calc = KlaimKomisiItem::calculateCommission(
                            $depositAmount,
                            $roiPct,
                            $masterSharePct,
                            $affiliateSharePct
                        );

                        $item->update([
                            'roi_percentage'           => $roiPct,
                            'master_share_pct'         => $masterSharePct,
                            'affiliate_share_pct'      => $affiliateSharePct,
                            'gross_profit'             => $calc['gross_profit'],
                            'master_gross_share'       => $calc['master_gross_share'],
                            'master_gross_share_idr'   => (int) round($calc['master_gross_share'] * $finalKursIdr),
                            'affiliate_commission'     => $calc['affiliate_commission'],
                            'affiliate_commission_idr' => (int) round($calc['affiliate_commission'] * $finalKursIdr),
                            'master_net_share'         => $calc['master_net_share'],
                            'investor_net_profit'      => $calc['investor_net_profit'],
                            'profit_master'            => $calc['master_gross_share'],
                            'nominal_transfer'         => $calc['affiliate_commission'],
                        ]);

                        $totalGrossProfit += $calc['gross_profit'];
                        $totalMasterGrossShare += $calc['master_gross_share'];
                        $totalAffiliateCommission += $calc['affiliate_commission'];
                        $totalMasterNetShare += $calc['master_net_share'];
                        $totalInvestorNetProfit += $calc['investor_net_profit'];
                    }
                } else {
                    // Skema Baru: Dihitung langsung dari 3 laporan bulanan pertama downline (berbasis fee Rupiah riil)
                    $totalNominalTransferIdr = 0;
                    foreach ($items as $item) {
                        $reports = LaporanProfitBulanan::query()
                            ->where('akun_copytrade_id', $item->akun_copytrade_id)
                            ->whereIn('periode_ke', [1, 2, 3])
                            ->get();

                        $masterGrossShare = (float) $reports->sum('fee_usd');
                        $grossProfit = (float) $reports->sum('profit_usd');
                        $affiliateSharePct = (float) ($item->affiliate_share_pct ?: $item->persentase_tier);
                        $depositAmount = (float) ($item->deposit_amount ?: $item->modal_riil);

                        $masterGrossShareIdr = (int) $reports->sum('fee_idr');
                        if ($masterGrossShareIdr === 0 && (int) $item->master_gross_share_idr > 0) {
                            $masterGrossShareIdr = (int) $item->master_gross_share_idr;
                        }
                        $itemAffiliateCommissionIdr = (int) round($masterGrossShareIdr * ($affiliateSharePct / 100));
                        if ($itemAffiliateCommissionIdr === 0 && (int) $item->affiliate_commission_idr > 0) {
                            $itemAffiliateCommissionIdr = (int) $item->affiliate_commission_idr;
                        }

                        if ($reports->isNotEmpty()) {
                            $calc = KlaimKomisiItem::calculateCommissionFromPaidFee(
                                $masterGrossShare,
                                $grossProfit,
                                $affiliateSharePct,
                                $depositAmount
                            );
                        } elseif ($item->master_gross_share !== null) {
                            $calc = KlaimKomisiItem::calculateCommissionFromPaidFee(
                                (float) $item->master_gross_share,
                                (float) ($item->gross_profit ?? 0),
                                $affiliateSharePct,
                                $depositAmount
                            );
                        } else {
                            $calc = KlaimKomisiItem::calculateCommissionFromPaidFee(
                                0.0,
                                0.0,
                                $affiliateSharePct,
                                $depositAmount
                            );
                        }

                        // Fallback IDR jika masih 0 tapi USD komisi > 0 (misal legacy mock)
                        if ($itemAffiliateCommissionIdr === 0 && $calc['affiliate_commission'] > 0) {
                            $itemAffiliateCommissionIdr = (int) round($calc['affiliate_commission'] * $finalKursIdr);
                            $masterGrossShareIdr = (int) round($calc['master_gross_share'] * $finalKursIdr);
                        }

                        $item->update([
                            'roi_percentage'           => $calc['roi_percentage'],
                            'gross_profit'             => $calc['gross_profit'],
                            'master_gross_share'       => $calc['master_gross_share'],
                            'master_gross_share_idr'   => $masterGrossShareIdr,
                            'affiliate_commission'     => $calc['affiliate_commission'],
                            'affiliate_commission_idr' => $itemAffiliateCommissionIdr,
                            'master_net_share'         => $calc['master_net_share'],
                            'investor_net_profit'      => $calc['investor_net_profit'],
                            'profit_master'            => $calc['master_gross_share'],
                            'nominal_transfer'         => $calc['affiliate_commission'],
                        ]);

                        $totalGrossProfit += $calc['gross_profit'];
                        $totalMasterGrossShare += $calc['master_gross_share'];
                        $totalAffiliateCommission += $calc['affiliate_commission'];
                        $totalMasterNetShare += $calc['master_net_share'];
                        $totalInvestorNetProfit += $calc['investor_net_profit'];
                        $totalNominalTransferIdr += $itemAffiliateCommissionIdr;
                    }
                }

                $transferPath = $buktiTransfer->store('commission_claims/transfers', 'local');

                // Tentukan total IDR transfer final
                if (!isset($totalNominalTransferIdr) || $totalNominalTransferIdr <= 0) {
                    if ((float) $claim->total_nominal_transfer_idr > 0) {
                        $totalNominalTransferIdr = (float) $claim->total_nominal_transfer_idr;
                    } else {
                        $totalNominalTransferIdr = round($totalAffiliateCommission * $finalKursIdr, 2);
                    }
                }

                // Hitung kurs konversi efektif
                if ($kursKonversiIdr !== null && $kursKonversiIdr > 0) {
                    $effectiveKurs = $kursKonversiIdr;
                } elseif ($totalAffiliateCommission > 0 && $totalNominalTransferIdr > 0) {
                    $effectiveKurs = round($totalNominalTransferIdr / $totalAffiliateCommission, 2);
                } else {
                    $effectiveKurs = $finalKursIdr;
                }

                $claim->update([
                    'status'                     => $status,
                    'total_gross_profit'         => $totalGrossProfit,
                    'total_master_gross_share'   => $totalMasterGrossShare,
                    'total_master_net_share'     => $totalMasterNetShare,
                    'total_investor_net_profit'  => $totalInvestorNetProfit,
                    'total_affiliate_commission' => $totalAffiliateCommission,
                    'total_profit_master'        => $totalMasterGrossShare,
                    'total_nominal_transfer'     => $totalAffiliateCommission,
                    'kurs_konversi_idr'          => $effectiveKurs,
                    'total_nominal_transfer_idr' => $totalNominalTransferIdr,
                    'bukti_transfer_path'        => $transferPath,
                    'catatan_admin'              => $catatanAdmin ?: null,
                    'processed_by'               => $processor->id,
                    'processed_at'               => now(),
                ]);

                // Kirim notifikasi in-app ke Affiliator
                $claim->affiliator->notify(new CommissionClaimProcessedNotification($claim, $status, $catatanAdmin));

                // Audit Log: Pencairan komisi disetujui & ditransfer
                $actor = AuditLogService::formatActor($processor);
                $affiliatorTag = AuditLogService::formatTargetUser($claim->affiliator, $claim->affiliator_id);
                $this->getAuditLogService()->record(
                    kategori: 'komisi_afiliasi',
                    aksi: 'proses_klaim_komisi',
                    deskripsi: "{$actor} menyelesaikan pencairan komisi {$claim->kode_klaim} milik {$affiliatorTag} sebesar \${$totalAffiliateCommission} (Rp " . number_format($totalNominalTransferIdr, 0, ',', '.') . ") ke rekening {$claim->nama_bank} - {$claim->nomor_rekening} ({$claim->atas_nama_rekening}).",
                    subject: $claim,
                    perubahan: [
                        'sebelum' => ['status' => 'pending'],
                        'sesudah' => [
                            'status'                     => $status,
                            'total_affiliate_commission' => $totalAffiliateCommission,
                            'total_nominal_transfer_idr' => $totalNominalTransferIdr,
                            'kurs_konversi_idr'          => $finalKursIdr,
                        ],
                        'meta' => [
                            'affiliator_id'  => $claim->affiliator_id,
                            'nama_bank'      => $claim->nama_bank,
                            'nomor_rekening' => $claim->nomor_rekening,
                        ],
                    ],
                    tingkat: 'critical',
                    user: $processor
                );

                return $claim;
            });
        }

        // Status rejected
        if (empty(trim((string) $catatanAdmin))) {
            throw ValidationException::withMessages([
                'catatan_admin' => 'Alasan penolakan wajib diisi untuk memberikan kejelasan kepada Affiliator.',
            ]);
        }

        return DB::transaction(function () use ($claim, $status, $catatanAdmin, $processor) {
            $claim->update([
                'status'        => $status,
                'catatan_admin' => $catatanAdmin,
                'processed_by'  => $processor->id,
                'processed_at'  => now(),
            ]);

            // Kirim notifikasi in-app ke Affiliator
            $claim->affiliator->notify(new CommissionClaimProcessedNotification($claim, $status, $catatanAdmin));

            // Audit Log: Penolakan klaim komisi
            $actor = AuditLogService::formatActor($processor);
            $affiliatorTag = AuditLogService::formatTargetUser($claim->affiliator, $claim->affiliator_id);
            $this->getAuditLogService()->record(
                kategori: 'komisi_afiliasi',
                aksi: 'proses_klaim_komisi',
                deskripsi: "{$actor} menolak pengajuan klaim komisi {$claim->kode_klaim} milik {$affiliatorTag}. Alasan: {$catatanAdmin}",
                subject: $claim,
                perubahan: [
                    'sebelum' => ['status' => 'pending'],
                    'sesudah' => [
                        'status'        => $status,
                        'catatan_admin' => $catatanAdmin,
                    ],
                    'meta' => [
                        'affiliator_id' => $claim->affiliator_id,
                    ],
                ],
                tingkat: 'warning',
                user: $processor
            );

            return $claim;
        });
    }

    /**
     * Ekspor data klaim pencairan komisi afiliasi ke format XLSX.
     *
     * @param array<string, mixed> $filters
     */
    public function exportXlsx(array $filters = []): BinaryFileResponse
    {
        $columns = [
            ['label' => 'Kode Klaim', 'width' => 18, 'type' => 'text'],
            ['label' => 'Tanggal Pengajuan', 'width' => 20],
            ['label' => 'Nama Affiliator', 'width' => 25],
            ['label' => 'Username Affiliator', 'width' => 18],
            ['label' => 'Nama Bank', 'width' => 18],
            ['label' => 'Nomor Rekening', 'width' => 22, 'type' => 'text'],
            ['label' => 'Atas Nama Rekening', 'width' => 25],
            ['label' => 'Jumlah Investor Downline', 'width' => 24, 'align' => 'center', 'type' => 'number'],
            ['label' => 'Jumlah Akun Copytrade', 'width' => 22, 'align' => 'center', 'type' => 'number'],
            ['label' => 'Daftar Investor Downline', 'width' => 40],
            ['label' => 'Total Modal Riil (USD)', 'width' => 22, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Estimasi Modal (IDR)', 'width' => 24, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Gross Profit (USD)', 'width' => 20, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Master Share (USD)', 'width' => 20, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Komisi Afiliasi (USD)', 'width' => 22, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Kurs Konversi (IDR)', 'width' => 20, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Nominal Transfer Komisi (IDR)', 'width' => 26, 'align' => 'right', 'type' => 'number'],
            ['label' => 'Status Pencairan', 'width' => 22, 'align' => 'center'],
            ['label' => 'Diproses Oleh', 'width' => 22],
            ['label' => 'Waktu Diproses', 'width' => 20],
            ['label' => 'Catatan Admin', 'width' => 35],
        ];

        $query = KlaimKomisi::with(['affiliator', 'processor', 'items.downline']);

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'approved_transferred', 'rejected'], true)) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('kode_klaim', 'like', "%{$search}%")
                    ->orWhereHas('affiliator', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.downline', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $direction = strtolower($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'kode_klaim', 'total_downline', 'total_akun', 'total_modal_riil', 'total_nominal_transfer', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->latest();
        }

        // Audit Log
        $actor = Auth::user();
        if ($actor) {
            $this->getAuditLogService()->record(
                kategori: 'komisi_afiliasi',
                aksi: 'ekspor_data',
                deskripsi: AuditLogService::formatActor($actor) . ' mengekspor data klaim pencairan komisi afiliasi ke format XLSX.',
                tingkat: 'info',
                user: $actor
            );
        }

        $kursUsdIdr = $this->getUsdToIdrRate();
        $xlsxService = app(XlsxExportService::class);

        return $xlsxService->download('klaim_komisi_afiliasi_yzd', 'Klaim Komisi', $columns, function ($addRow) use ($query, $kursUsdIdr): void {
            $query->chunk(500, function ($claims) use ($addRow, $kursUsdIdr): void {
                foreach ($claims as $claim) {
                    $uniqueDownlines = $claim->items->pluck('downline.name')->filter()->unique()->values();
                    $downlinesSummary = $uniqueDownlines->isNotEmpty() ? $uniqueDownlines->join(', ') : '1 Investor';

                    $statusLabel = match ($claim->status) {
                        'approved_transferred' => 'Ditransfer (Selesai)',
                        'rejected'             => 'Ditolak',
                        default                => 'Menunggu Verifikasi',
                    };

                    $totalModalUsd = (float) $claim->total_modal_riil;
                    $totalModalIdr = round($totalModalUsd * $kursUsdIdr, 2);

                    $addRow([
                        (string) $claim->kode_klaim,
                        $claim->created_at ? $claim->created_at->format('Y-m-d H:i') : '-',
                        $claim->affiliator?->name ?? '-',
                        $claim->affiliator?->username ? '@' . $claim->affiliator->username : '-',
                        $claim->nama_bank,
                        (string) $claim->nomor_rekening,
                        $claim->atas_nama_rekening,
                        $claim->total_downline,
                        $claim->total_akun,
                        $downlinesSummary,
                        $totalModalUsd,
                        $totalModalIdr,
                        $claim->total_gross_profit !== null ? (float) $claim->total_gross_profit : 0,
                        $claim->total_master_gross_share !== null ? (float) $claim->total_master_gross_share : 0,
                        $claim->total_affiliate_commission !== null ? (float) $claim->total_affiliate_commission : 0,
                        $claim->kurs_konversi_idr !== null ? (float) $claim->kurs_konversi_idr : $kursUsdIdr,
                        $claim->total_nominal_transfer_idr !== null ? (float) $claim->total_nominal_transfer_idr : ($claim->total_nominal_transfer ? round((float) $claim->total_nominal_transfer * $kursUsdIdr, 2) : 0),
                        $statusLabel,
                        $claim->processor?->name ?? '-',
                        $claim->processed_at ? \Carbon\Carbon::parse($claim->processed_at)->format('Y-m-d H:i') : '-',
                        $claim->catatan_admin ?? '-',
                    ]);
                }
            });
        });
    }
}
