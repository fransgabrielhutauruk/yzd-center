<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LaporanProfitBulanan;
use App\Services\CommissionClaimService;
use App\Services\ProfitSummaryService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected ProfitSummaryService $profitSummaryService,
        protected CommissionClaimService $commissionClaimService,
        protected ReferralService $referralService
    ) {}

    /**
     * Tampilkan halaman utama dasbor terpadu Member.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $unpaidReports = LaporanProfitBulanan::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                LaporanProfitBulanan::STATUS_BELUM_DIBAYAR,
                LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI,
                LaporanProfitBulanan::STATUS_DITOLAK,
            ]);

        return Inertia::render('Member/Dashboard', [
            'user' => [
                'name'           => $user->name,
                'role'           => $user->role,
                'referral_code'  => $user->referral_code,
                'derived_labels' => $user->getDerivedLabels(),
                'is_investor'    => $user->isInvestorLabel(),
                'is_affiliator'  => $user->isAffiliatorLabel(),
            ],
            'dashboard' => $this->profitSummaryService->getDashboardSummary($user),
            'affiliate' => [
                'network'    => $this->referralService->getAffiliatorNetworkStats($user),
                'commission' => $this->commissionClaimService->getAffiliatorCommissionStats($user),
            ],
            'master_fee' => [
                'unpaid_count' => (clone $unpaidReports)->count(),
                'unpaid_usd'   => round((float) (clone $unpaidReports)->sum('fee_usd'), 2),
            ],
        ]);
    }
}
