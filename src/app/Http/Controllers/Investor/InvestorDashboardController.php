<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use App\Services\CommissionClaimService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestorDashboardController extends Controller
{
    public function __construct(
        protected BalanceService $balanceService,
        protected CommissionClaimService $commissionClaimService,
        protected ReferralService $referralService
    ) {}

    /**
     * Tampilkan halaman utama dasbor (terpisah antara investor dan affiliator).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user && $user->isAffiliator()) {
            return Inertia::render('Investor/AffiliatorDashboard', [
                'user' => [
                    'name'          => $user->name,
                    'role'          => $user->role,
                    'referral_code' => $user->referral_code,
                ],
                'dashboard' => $this->balanceService->getDashboardSummary($user),
                'affiliate' => [
                    'network'    => $this->referralService->getAffiliatorNetworkStats($user),
                    'commission' => $this->commissionClaimService->getAffiliatorCommissionStats($user),
                ],
            ]);
        }

        return Inertia::render('Investor/InvestorDashboard', [
            'user' => [
                'name' => $user->name,
                'role' => $user->role,
            ],
            'dashboard' => $this->balanceService->getDashboardSummary($user),
        ]);
    }
}
