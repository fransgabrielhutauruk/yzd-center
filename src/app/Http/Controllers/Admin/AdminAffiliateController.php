<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KlaimReferral;
use App\Models\RelasiReferral;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAffiliateController extends Controller
{
    public function __construct(
        private readonly ReferralService $referralService,
    ) {}

    /**
     * Tampilkan pemantauan rekapitulasi seluruh jaringan mitra affiliator & verifikasi klaim susulan referral.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'per_page']);
        $claimFilters = $request->only([
            'claim_search',
            'claim_status',
            'claim_sort_by',
            'claim_sort_direction',
            'claim_per_page',
        ]);

        $totalAffiliators = User::where('role', 'affiliator')->where('is_active', true)->count();
        $totalDownlines = RelasiReferral::whereHas('downline', fn ($q) => $q->where('is_active', true))->count();
        $pendingClaimsCount = KlaimReferral::where('status', 'pending')->count();

        return Inertia::render('Admin/Affiliates/Index', [
            'affiliates'         => $this->referralService->getAllAffiliatesSummary($filters, 'page'),
            'claims'             => $this->referralService->getPaginatedClaims($claimFilters, null, 'claim_page'),
            'filters'            => $filters,
            'claimFilters'       => $claimFilters,
            'pendingClaimsCount' => $pendingClaimsCount,
            'activeTab'          => $request->query('tab', 'affiliates'),
            'stats'              => [
                'total_affiliators' => $totalAffiliators,
                'total_downlines'   => $totalDownlines,
                'active_downlines'  => $totalDownlines,
                'pending_claims'    => $pendingClaimsCount,
            ],
        ]);
    }
}
