<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\LaporanProfitBulanan;
use App\Models\User;
use App\Support\ProfitPeriod;
use Carbon\Carbon;

class ProfitSummaryService
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {}

    /**
     * Menghitung ringkasan konsolidasi portofolio seluruh akun investasi milik member
     * berdasarkan laporan profit bulanan (Myfxbook + WD).
     */
    public function getPortfolioConsolidatedSummary(User $user): array
    {
        $accounts = AkunCopytrade::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->with(['laporanProfit' => fn ($q) => $q->orderBy('periode_ke', 'asc')])
            ->orderBy('id', 'asc')
            ->get();

        $kursData = $this->exchangeRateService->getUsdToIdrRate();
        $kurs = (float) ($kursData['rate'] ?? 16796.0);

        $activeAccounts = $accounts->where('status', 'active');
        $pendingAccounts = $accounts->where('status', 'pending');
        $activeCount = $activeAccounts->count();
        $pendingCount = $pendingAccounts->count();

        if ($activeAccounts->isEmpty()) {
            return [
                'total_accounts'            => $accounts->count(),
                'active_accounts_count'     => 0,
                'pending_accounts_count'    => $pendingCount,
                'has_active_accounts'       => false,
                'total_initial_capital'     => 0.0,
                'total_initial_capital_idr' => 0,
                'total_current_balance'     => 0.0,
                'total_current_balance_idr' => 0,
                'total_all_time_profit'     => 0.0,
                'total_all_time_profit_idr' => 0,
                'total_all_time_growth_pct' => 0.0,
                'total_all_time_wd'         => 0.0,
                'total_all_time_wd_idr'     => 0,
                'has_balance_history'       => false,
                'bep_summary'               => [
                    'bep_progress_pct'         => 0.0,
                    'initial_capital'          => 0.0,
                    'initial_capital_idr'      => 0,
                    'total_withdrawn'          => 0.0,
                    'total_withdrawn_idr'      => 0,
                    'total_compounded'         => 0.0,
                    'total_compounded_idr'     => 0,
                    'remaining_capital_to_bep' => 0.0,
                    'remaining_capital_idr'    => 0,
                    'remaining_to_bep'         => 0.0,
                    'remaining_to_bep_idr'     => 0,
                    'is_bep_achieved'          => false,
                    'months_to_bep'            => 23,
                    'estimated_months_to_bep'  => 23,
                    'remaining_months_to_bep'  => 23,
                    'avg_monthly_profit_pct'   => 0.0,
                    'months_active'            => 1,
                    'joined_months_count'      => 1,
                    'join_date_formatted'      => null,
                    'total_profit_accumulated' => 0.0,
                    'total_profit_idr'         => 0,
                ],
            ];
        }

        $totalInitialCapital = 0.0;
        $totalAllTimeProfit = 0.0;
        $totalAllTimeWd = 0.0;
        $hasAnyReport = false;

        foreach ($activeAccounts as $acc) {
            $modalAwal = (float) $acc->modal_riil;
            $totalInitialCapital += $modalAwal;

            $reports = $acc->laporanProfit;
            if ($reports->isNotEmpty()) {
                $hasAnyReport = true;
                $totalAllTimeProfit += (float) $reports->sum('profit_usd');
                $totalAllTimeWd += (float) $reports->sum('wd_usd');
            }
        }

        $totalCurrentBalance = $totalInitialCapital + $totalAllTimeProfit;
        $totalAllTimeGrowthPct = $totalInitialCapital > 0
            ? round(($totalAllTimeProfit / $totalInitialCapital) * 100.0, 2)
            : 0.0;

        $bepProgressPct = $totalInitialCapital > 0
            ? min(100.0, max(0.0, round(($totalAllTimeProfit / $totalInitialCapital) * 100.0, 1)))
            : 0.0;

        $remainingToBep = max(0.0, $totalInitialCapital - max(0.0, $totalAllTimeProfit));
        $isBepAchieved = $totalAllTimeProfit >= $totalInitialCapital && $totalInitialCapital > 0;

        $earliestActiveAccount = $activeAccounts->sortBy('created_at')->first();
        $joinDate = $earliestActiveAccount?->tanggal_mulai ?? $earliestActiveAccount?->created_at;
        $startCarbon = $joinDate ? Carbon::parse($joinDate) : now();
        $diffMonths = (int) $startCarbon->diffInMonths(now());
        $monthsActive = max(1, $diffMonths > 0 ? $diffMonths : 1);

        $avgMonthlyProfitPct = $monthsActive > 0 ? round($totalAllTimeGrowthPct / $monthsActive, 2) : 0.0;
        if ($avgMonthlyProfitPct <= 0 && $hasAnyReport) {
            $estimatedMonthsToBep = null;
        } else {
            $effectiveMonthlyRate = $avgMonthlyProfitPct > 0 ? ($avgMonthlyProfitPct / 100.0) : 0.035;
            $estimatedMonthsToBep = ($totalInitialCapital > 0 && $effectiveMonthlyRate > 0)
                ? (int) ceil(log(2) / log(1 + $effectiveMonthlyRate))
                : 21;
        }

        $remainingMonthsToBep = $isBepAchieved ? 0 : max(0, ($estimatedMonthsToBep ?: 23) - $monthsActive);

        return [
            'total_accounts'            => $accounts->count(),
            'active_accounts_count'     => $activeCount,
            'pending_accounts_count'    => $pendingCount,
            'has_active_accounts'       => true,
            'total_initial_capital'     => round($totalInitialCapital, 2),
            'total_initial_capital_idr' => round($totalInitialCapital * $kurs, 0),
            'total_current_balance'     => round($totalCurrentBalance, 2),
            'total_current_balance_idr' => round($totalCurrentBalance * $kurs, 0),
            'total_all_time_profit'     => round($totalAllTimeProfit, 2),
            'total_all_time_profit_idr' => round($totalAllTimeProfit * $kurs, 0),
            'total_all_time_growth_pct' => $totalAllTimeGrowthPct,
            'total_all_time_wd'         => round($totalAllTimeWd, 2),
            'total_all_time_wd_idr'     => round($totalAllTimeWd * $kurs, 0),
            'has_balance_history'       => $hasAnyReport,
            'bep_summary'               => [
                'bep_progress_pct'         => $bepProgressPct,
                'initial_capital'          => round($totalInitialCapital, 2),
                'initial_capital_idr'      => round($totalInitialCapital * $kurs, 0),
                'total_withdrawn'          => round($totalAllTimeWd, 2),
                'total_withdrawn_idr'      => round($totalAllTimeWd * $kurs, 0),
                'total_compounded'         => max(0.0, round($totalAllTimeProfit, 2)),
                'total_compounded_idr'     => max(0, round($totalAllTimeProfit * $kurs, 0)),
                'remaining_capital_to_bep' => round($remainingToBep, 2),
                'remaining_capital_idr'    => round($remainingToBep * $kurs, 0),
                'remaining_to_bep'         => round($remainingToBep, 2),
                'remaining_to_bep_idr'     => round($remainingToBep * $kurs, 0),
                'is_bep_achieved'          => $isBepAchieved,
                'months_to_bep'            => $estimatedMonthsToBep,
                'estimated_months_to_bep'  => $estimatedMonthsToBep,
                'remaining_months_to_bep'  => $remainingMonthsToBep,
                'avg_monthly_profit_pct'   => $avgMonthlyProfitPct,
                'months_active'            => $monthsActive,
                'joined_months_count'      => $monthsActive,
                'join_date_formatted'      => $earliestActiveAccount ? ($earliestActiveAccount->created_at ? (function_exists('tanggal_indo') ? tanggal_indo($earliestActiveAccount->created_at) : Carbon::parse($earliestActiveAccount->created_at)->format('d M Y')) : null) : null,
                'total_profit_accumulated' => max(0.0, round($totalAllTimeProfit, 2)),
                'total_profit_idr'         => max(0, round($totalAllTimeProfit * $kurs, 0)),
            ],
        ];
    }

    /**
     * Ringkasan performa per akun copytrade berdasarkan laporan profit bulanan.
     */
    public function getPerformanceSummary(
        AkunCopytrade $akun,
        string $period = 'all',
        ?string $customStart = null,
        ?string $customEnd = null
    ): array {
        $kursData = $this->exchangeRateService->getUsdToIdrRate();
        $kurs = (float) ($kursData['rate'] ?? 16796.0);
        $modalAwal = (float) $akun->modal_riil;

        $allReports = $akun->laporanProfit()
            ->orderBy('periode_ke', 'asc')
            ->get();

        $totalProfit = (float) $allReports->sum('profit_usd');
        $totalWd = (float) $allReports->sum('wd_usd');
        $currentBalance = $modalAwal + $totalProfit;

        $allTimeGrowthPct = $modalAwal > 0
            ? round(($totalProfit / $modalAwal) * 100.0, 2)
            : 0.0;

        $reportsCount = $allReports->count();
        $avgMonthlyProfitPct = $reportsCount > 0
            ? round($allTimeGrowthPct / $reportsCount, 2)
            : 0.0;

        $runningBalance = $modalAwal;
        $formattedHistory = $allReports->map(function (LaporanProfitBulanan $rec) use ($kurs, $modalAwal, &$runningBalance) {
            $pAwal = Carbon::parse($rec->periode_awal);
            $pAkhir = Carbon::parse($rec->periode_akhir);
            $profit = (float) $rec->profit_usd;
            $wd = (float) ($rec->wd_usd ?? 0);
            $runningBalance += $profit;
            $profitPct = $modalAwal > 0 ? round(($profit / $modalAwal) * 100.0, 2) : 0.0;

            return [
                'id'                  => $rec->id,
                'nomor'               => $rec->nomor,
                'periode_ke'          => $rec->periode_ke,
                'tanggal_pencatatan'  => $rec->periode_akhir->toDateString(),
                'tanggal_formatted'   => $pAwal->translatedFormat('d M') . ' - ' . $pAkhir->translatedFormat('d M Y'),
                'balance'             => round($runningBalance, 2),
                'balance_idr'         => round($runningBalance * $kurs, 0),
                'profit_usd'          => $profit,
                'profit_idr'          => round($profit * $kurs, 0),
                'wd_usd'              => $wd,
                'wd_idr'              => $rec->wd_idr ?? (int) round($wd * $kurs, 0),
                'kurs_riil'           => $rec->kurs_riil,
                'keuntungan_nominal'  => $profit,
                'keuntungan_persen'   => $profitPct,
                'fee_usd'             => (float) $rec->fee_usd,
                'fee_idr'             => (int) $rec->fee_idr,
                'status'              => $rec->status,
                'status_tampil'       => $rec->status_tampil,
                'status_catatan'      => $rec->status_catatan,
                'bukti_myfxbook_path' => $rec->bukti_myfxbook_path,
                'bukti_wd_path'       => $rec->bukti_wd_path,
                'bukti_transfer_path' => $rec->bukti_transfer_path,
            ];
        })->values()->all();

        return [
            'account' => [
                'id'                 => $akun->id,
                'nomor_akun_trading' => $akun->nomor_akun_trading,
                'broker'             => $akun->broker,
                'tier'               => $akun->tier,
                'modal_riil'         => $modalAwal,
                'modal_riil_idr'     => round($modalAwal * $kurs, 0),
                'myfxbook_url'       => $akun->myfxbook_url,
                'tanggal_mulai'      => $akun->tanggal_mulai?->format('Y-m-d'),
                'tanggal_mulai_indo' => function_exists('tanggal_indo') ? tanggal_indo($akun->tanggal_mulai) : $akun->tanggal_mulai?->format('d M Y'),
                'status'             => $akun->status,
            ],
            'current_balance'             => round($currentBalance, 2),
            'current_balance_idr'         => round($currentBalance * $kurs, 0),
            'all_time_profit_nominal'     => round($totalProfit, 2),
            'all_time_profit_nominal_idr' => round($totalProfit * $kurs, 0),
            'all_time_growth_pct'         => $allTimeGrowthPct,
            'total_wd_usd'                => round($totalWd, 2),
            'total_wd_idr'                => round($totalWd * $kurs, 0),
            'avg_monthly_profit_pct'      => $avgMonthlyProfitPct,
            'history'                     => $formattedHistory,
            'total_records_count'         => $reportsCount,
            'exchange_rate'               => $kurs,
        ];
    }

    /**
     * Ringkasan dasbor investor untuk halaman Member/Dashboard.
     */
    public function getDashboardSummary(User $user): array
    {
        $consolidated = $this->getPortfolioConsolidatedSummary($user);

        $allUserAccounts = AkunCopytrade::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->with(['latestLaporanProfit', 'laporanProfit'])
            ->orderBy('id', 'asc')
            ->get();

        $activeAccounts = $allUserAccounts->where('status', 'active')->values();
        $stage = $activeAccounts->isNotEmpty()
            ? 'running'
            : ($allUserAccounts->isNotEmpty() ? 'pending_account' : 'no_account');

        $now = Carbon::now();
        $unrecordedCount = 0;
        foreach ($activeAccounts as $acc) {
            if (ProfitPeriod::nextReportablePeriod($acc, $now) !== null) {
                $unrecordedCount++;
            }
        }

        $accountsSummary = $activeAccounts->map(function (AkunCopytrade $acc) {
            $modal = (float) $acc->modal_riil;
            $latest = $acc->latestLaporanProfit;
            $profit = $latest ? (float) $latest->profit_usd : null;
            $currentBal = $profit !== null ? $modal + $profit : $modal;
            $pct = ($profit !== null && $modal > 0) ? round(($profit / $modal) * 100, 2) : 0.0;

            return [
                'id'                 => $acc->id,
                'nomor_akun_trading' => $acc->nomor_akun_trading,
                'broker'             => $acc->broker,
                'tier'               => $acc->tier,
                'status'             => $acc->status,
                'modal_riil'         => $modal,
                'current_balance'    => round($currentBal, 2),
                'profit'             => $profit,
                'profit_pct'         => $pct,
                'myfxbook_url'       => $acc->myfxbook_url,
                'last_recorded_at'   => $latest?->periode_akhir ? (function_exists('tanggal_indo') ? tanggal_indo($latest->periode_akhir) : $latest->periode_akhir->format('d M Y')) : null,
            ];
        })->all();

        // 6 Bulan Tren Profit Kumulatif
        $trendMonths = [];
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $allReportsUser = LaporanProfitBulanan::query()
            ->where('user_id', $user->id)
            ->where('periode_akhir', '>=', $sixMonthsAgo->toDateString())
            ->orderBy('periode_akhir', 'asc')
            ->get();

        for ($i = 5; $i >= 0; $i--) {
            $monthTarget = Carbon::now()->subMonths($i);
            $ym = $monthTarget->format('Y-m');
            $reportsInMonth = $allReportsUser->filter(fn ($r) => Carbon::parse($r->periode_akhir)->format('Y-m') === $ym);
            $trendMonths[] = [
                'month'      => $monthTarget->translatedFormat('M Y'),
                'profit_usd' => round((float) $reportsInMonth->sum('profit_usd'), 2),
                'wd_usd'     => round((float) $reportsInMonth->sum('wd_usd'), 2),
            ];
        }

        $bep = $consolidated['bep_summary'];

        return [
            'stage'     => $stage,
            'portfolio' => [
                'active_accounts_count'     => $consolidated['active_accounts_count'],
                'pending_accounts_count'    => $consolidated['pending_accounts_count'],
                'has_balance_history'       => $consolidated['has_balance_history'],
                'total_initial_capital'     => $consolidated['total_initial_capital'],
                'total_modal_riil'          => $consolidated['total_initial_capital'],
                'total_current_balance'     => $consolidated['total_current_balance'],
                'total_all_time_profit'     => $consolidated['total_all_time_profit'],
                'total_all_time_profit_pct' => $consolidated['total_all_time_growth_pct'],
                'total_all_time_growth_pct' => $consolidated['total_all_time_growth_pct'],
                'bep_percentage'            => (float) $bep['bep_progress_pct'],
                'bep_remaining'             => (float) $bep['remaining_to_bep'],
                'bep_achieved'              => (bool) $bep['is_bep_achieved'],
            ],
            'balance_recorded_this_month' => $stage === 'running' && $unrecordedCount === 0,
            'unrecorded_accounts_count'   => $unrecordedCount,
            'consolidated'                => $consolidated,
            'accounts'                    => $accountsSummary,
            'trend'                       => $trendMonths,
        ];
    }
}
