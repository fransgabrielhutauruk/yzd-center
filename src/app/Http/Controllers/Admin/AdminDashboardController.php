<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunCopytrade;
use App\Models\Invitation;
use App\Models\KlaimKomisi;
use App\Models\KlaimReferral;
use App\Models\LaporanProfitBulanan;
use App\Models\TiketBantuan;
use App\Models\User;
use App\Support\ProfitPeriod;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Tampilkan halaman utama dasbor admin.
     */
    public function index(): Response
    {
        // 1. Antrean butuh tindakan (permohonan tertunda)
        $pendingKyc = User::query()
            ->where('role', 'member')
            ->where('status', '!=', 'active')
            ->count();

        $pendingCopytrade = AkunCopytrade::query()
            ->where('status', 'pending')
            ->count();

        $pendingReferralClaims = KlaimReferral::query()
            ->where('status', 'pending')
            ->count();

        $pendingCommissionClaims = KlaimKomisi::query()
            ->where('status', 'pending')
            ->count();

        $pendingTickets = TiketBantuan::query()
            ->butuhResponsAdmin()
            ->count();

        $pendingPerformanceFee = LaporanProfitBulanan::query()
            ->where('status', LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI)
            ->count();

        // 2. Monitoring tagihan & laporan Performance Fee investor (belum kirim bukti & belum lapor periode terbuka)
        $unpaidReports = LaporanProfitBulanan::query()
            ->whereIn('status', [
                LaporanProfitBulanan::STATUS_BELUM_DIBAYAR,
                LaporanProfitBulanan::STATUS_DITOLAK,
            ])
            ->get(['id', 'user_id', 'status', 'fee_usd', 'fee_idr']);

        $unpaidReportsCount = $unpaidReports->count();
        $unpaidOnlyCount = $unpaidReports->where('status', LaporanProfitBulanan::STATUS_BELUM_DIBAYAR)->count();
        $rejectedReportsCount = $unpaidReports->where('status', LaporanProfitBulanan::STATUS_DITOLAK)->count();
        $unpaidInvestorsCount = $unpaidReports->pluck('user_id')->filter()->unique()->count();
        $unpaidTotalUsd = round((float) $unpaidReports->sum('fee_usd'), 2);
        $unpaidTotalIdr = (int) $unpaidReports->sum('fee_idr');

        $today = Carbon::now();
        $activeCopytradeAccounts = AkunCopytrade::query()
            ->where('status', 'active')
            ->get(['id', 'user_id', 'tanggal_mulai', 'status']);

        $missingAccountsCount = 0;
        $missingInvestorIds = [];
        foreach ($activeCopytradeAccounts as $acc) {
            if (ProfitPeriod::nextReportablePeriod($acc, $today) !== null) {
                $missingAccountsCount++;
                $missingInvestorIds[$acc->user_id] = true;
            }
        }
        $missingInvestorsCount = count($missingInvestorIds);

        // 3. Metrik ekosistem
        $memberCount = User::where('role', 'member')->count();
        $activeMemberCount = User::where('role', 'member')->where('is_active', true)->count();

        $activeInvestorCount = User::activeInvestors()->count();
        $investorCount = User::where('role', 'member')->whereHas('akunCopytrade')->count();

        $activeAffiliatorCount = User::activeAffiliators()->count();
        $affiliatorCount = User::where('role', 'member')->whereHas('relasiDownlines')->count();

        $activeCopytradeCount = AkunCopytrade::where('status', 'active')->count();
        $activeInviteCount = Invitation::query()->active()->count();

        return Inertia::render('Admin/Dashboard', [
            'pending_actions' => [
                'kyc'               => $pendingKyc,
                'copytrade'         => $pendingCopytrade,
                'referral_claims'   => $pendingReferralClaims,
                'commission_claims' => $pendingCommissionClaims,
                'tickets'           => $pendingTickets,
                'performance_fee'   => $pendingPerformanceFee,
                'total'             => $pendingKyc + $pendingCopytrade + $pendingReferralClaims + $pendingCommissionClaims + $pendingTickets + $pendingPerformanceFee,
            ],
            'performance_fee_monitoring' => [
                'unpaid_reports_count'    => $unpaidReportsCount,
                'unpaid_only_count'       => $unpaidOnlyCount,
                'rejected_reports_count'  => $rejectedReportsCount,
                'unpaid_investors_count'  => $unpaidInvestorsCount,
                'unpaid_total_usd'        => $unpaidTotalUsd,
                'unpaid_total_idr'        => $unpaidTotalIdr,
                'missing_accounts_count'  => $missingAccountsCount,
                'missing_investors_count' => $missingInvestorsCount,
            ],
            'stats' => [
                'member_count'            => $memberCount,
                'active_member_count'     => $activeMemberCount,
                'investor_count'          => $investorCount,
                'active_investor_count'   => $activeInvestorCount,
                'affiliator_count'        => $affiliatorCount,
                'active_affiliator_count' => $activeAffiliatorCount,
                'active_copytrade_count'  => $activeCopytradeCount,
                'active_invites'          => $activeInviteCount,
            ],
        ]);
    }
}
