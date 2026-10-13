<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunCopytrade;
use App\Models\Invitation;
use App\Models\KlaimKomisi;
use App\Models\KlaimReferral;
use App\Models\User;
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
            ->whereIn('role', ['investor', 'affiliator'])
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

        // 2. Metrik ekosistem
        $investorCount = User::where('role', 'investor')->count();
        $activeInvestorCount = User::where('role', 'investor')->where('is_active', true)->count();

        $affiliatorCount = User::where('role', 'affiliator')->count();
        $activeAffiliatorCount = User::where('role', 'affiliator')->where('is_active', true)->count();

        $activeCopytradeCount = AkunCopytrade::where('status', 'active')->count();
        $activeInviteCount = Invitation::query()->active()->count();

        return Inertia::render('Admin/Dashboard', [
            'pending_actions' => [
                'kyc'               => $pendingKyc,
                'copytrade'         => $pendingCopytrade,
                'referral_claims'   => $pendingReferralClaims,
                'commission_claims' => $pendingCommissionClaims,
                'total'             => $pendingKyc + $pendingCopytrade + $pendingReferralClaims + $pendingCommissionClaims,
            ],
            'stats' => [
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
