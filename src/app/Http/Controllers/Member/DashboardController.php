<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use App\Services\CommissionClaimService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected BalanceService $balanceService,
        protected CommissionClaimService $commissionClaimService,
        protected ReferralService $referralService
    ) {}

    /**
     * Tampilkan halaman utama dasbor terpadu Member.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Member/Dashboard', [
            'user' => [
                'name'           => $user->name,
                'role'           => $user->role,
                'referral_code'  => $user->referral_code,
                'derived_labels' => $user->getDerivedLabels(),
                'is_investor'    => $user->isInvestorLabel(),
                'is_affiliator'  => $user->isAffiliatorLabel(),
            ],
            'dashboard' => $this->balanceService->getDashboardSummary($user),
            'affiliate' => [
                'network'    => $this->referralService->getAffiliatorNetworkStats($user),
                'commission' => $this->commissionClaimService->getAffiliatorCommissionStats($user),
            ],
        ]);
    }
}
