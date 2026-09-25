<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Services\EducationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestorDashboardController extends Controller
{
    public function __construct(
        protected EducationService $educationService
    ) {}

    /**
     * Tampilkan halaman utama dasbor investor.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $bepSummary = $this->educationService->getInvestorBepSummary($user);

        return Inertia::render('Investor/Dashboard', [
            'user' => [
                'name'          => $user->name,
                'email'         => $user->email,
                'role'          => $user->role,
                'referral_code' => $user->referral_code,
                'is_active'     => $user->is_active,
            ],
            'bep_summary' => $bepSummary,
        ]);
    }
}
