<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investor\StoreReferralClaimRequest;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateNetworkController extends Controller
{
    public function __construct(
        private readonly ReferralService $referralService,
    ) {}

    /**
     * Tampilkan dasbor jaringan kemitraan affiliator, tabel downline, dan riwayat klaim.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && $user->isAffiliator(), 403, 'Akses terbatas untuk akun Mitra Affiliator.');

        $downlineFilters = $request->only(['search', 'sumber', 'status', 'sort_by', 'sort_direction', 'per_page']);

        $claimFilters = array_filter([
            'claim_search'         => $request->query('claim_search'),
            'claim_status'         => $request->query('claim_status'),
            'claim_sort_by'        => $request->query('claim_sort_by'),
            'claim_sort_direction' => $request->query('claim_sort_direction'),
            'claim_per_page'       => $request->query('claim_per_page'),
        ], fn ($val) => filled($val));

        return Inertia::render('Investor/Affiliates/Index', [
            'stats'             => $this->referralService->getAffiliatorNetworkStats($user),
            'downlines'         => $this->referralService->getPaginatedDownlines($user, $downlineFilters),
            'downlineFilters'   => $downlineFilters,
            'claims'            => $this->referralService->getPaginatedClaims($claimFilters, $user, 'claim_page'),
            'claimFilters'      => $claimFilters,
            'eligibleDownlines' => $this->referralService->getEligibleDownlinesForClaim($user),
            'activeTab'         => $request->query('tab', 'downlines') === 'claims' ? 'claims' : 'downlines',
        ]);
    }

    /**
     * Simpan pengajuan klaim referral susulan oleh Affiliator.
     */
    public function storeClaim(StoreReferralClaimRequest $request): RedirectResponse
    {
        $user = $request->user();

        $this->referralService->submitClaim(
            $user,
            $request->integer('downline_id'),
            $request->file('bukti_chat'),
            $request->string('alasan')->value()
        );

        return redirect()->route('investor.affiliates.index')
            ->with('success', 'Permohonan klaim referral susulan berhasil diajukan dan sedang menunggu peninjauan Administrator.');
    }

    /**
     * Ambil daftar calon downline yang dapat diklaim secara realtime.
     */
    public function eligibleDownlines(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isAffiliator(), 403);

        return response()->json([
            'data' => $this->referralService->getEligibleDownlinesForClaim($user),
        ]);
    }

    /**
     * Periksa ketersediaan dan validitas username calon downline untuk klaim susulan.
     */
    public function checkDownline(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isAffiliator(), 403);

        $username = trim((string) $request->query('username'));

        if (empty($username)) {
            return response()->json([
                'valid'   => false,
                'message' => 'Silakan ketikkan username calon downline.',
            ], 422);
        }

        $result = $this->referralService->checkDownlineForClaim($user, $username);

        return response()->json($result, $result['valid'] ? 200 : 422);
    }
}
