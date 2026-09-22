<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * Tampilkan pemantauan rekapitulasi seluruh jaringan mitra affiliator.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'per_page']);

        $totalAffiliators = User::where('role', 'affiliator')->count();
        $totalDownlines = RelasiReferral::count();
        $activeDownlines = RelasiReferral::whereHas('downline', fn ($q) => $q->where('status', 'active'))->count();

        return Inertia::render('Admin/Affiliates/Index', [
            'affiliates' => $this->referralService->getAllAffiliatesSummary($filters),
            'filters'    => $filters,
            'stats'      => [
                'total_affiliators' => $totalAffiliators,
                'total_downlines'   => $totalDownlines,
                'active_downlines'  => $activeDownlines,
            ],
        ]);
    }
}
