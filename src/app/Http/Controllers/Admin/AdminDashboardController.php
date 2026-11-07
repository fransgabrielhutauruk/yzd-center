<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunCopytrade;
use App\Models\Invitation;
use App\Models\KlaimKomisi;
use App\Models\KlaimReferral;
use App\Models\TagihanMasterFee;
use App\Models\TiketBantuan;
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

        $pendingMasterFee = TagihanMasterFee::query()
            ->where('status', TagihanMasterFee::STATUS_MENUNGGU_VERIFIKASI)
            ->count();

        // 2. Metrik ekosistem
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
                'master_fee'        => $pendingMasterFee,
                'total'             => $pendingKyc + $pendingCopytrade + $pendingReferralClaims + $pendingCommissionClaims + $pendingTickets + $pendingMasterFee,
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
