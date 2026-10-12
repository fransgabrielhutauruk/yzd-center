<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investor\StoreCommissionClaimRequest;
use App\Services\CommissionClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommissionClaimController extends Controller
{
    public function __construct(
        private readonly CommissionClaimService $commissionClaimService
    ) {}

    /**
     * Tampilkan dasbor klaim komisi affiliator, kartu statistik, riwayat klaim, dan data downline eligible.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && $user->isAffiliator(), 403, 'Akses terbatas untuk akun Mitra Affiliator.');

        $filters = array_filter([
            'search'         => $request->query('search'),
            'status'         => $request->query('status'),
            'sort_by'        => $request->query('sort_by'),
            'sort_direction' => $request->query('sort_direction'),
            'per_page'       => $request->query('per_page'),
        ], fn ($val) => filled($val));

        return Inertia::render('Investor/Commissions/Index', [
            'stats'             => $this->commissionClaimService->getAffiliatorCommissionStats($user),
            'claims'            => $this->commissionClaimService->getPaginatedClaims($filters, $user),
            'filters'           => $filters,
            'eligibleDownlines' => $this->commissionClaimService->getEligibleDownlinesForCommission($user),
            'defaultBank'       => $this->commissionClaimService->getDefaultBankForAffiliator($user),
            'exchangeRate'      => $this->commissionClaimService->getExchangeRateInfo(),
        ]);
    }

    /**
     * Simpan formulir pengajuan klaim komisi oleh Affiliator.
     * Disimpan sebagai 1 row klaim komisi dengan detail item downline.
     */
    public function store(StoreCommissionClaimRequest $request): RedirectResponse
    {
        $claim = $this->commissionClaimService->submitBatchClaims(
            $request->user(),
            $request->validated()
        );

        $message = "Permohonan klaim komisi ({$claim->kode_klaim}) untuk {$claim->total_downline} investor ({$claim->total_akun} akun copytrade) berhasil diajukan dan sedang menunggu peninjauan Administrator.";

        return redirect()->route('investor.commissions.index')
            ->with('success', $message);
    }

    /**
     * Endpoint API JSON untuk memuat ulang daftar downline yang eligible.
     */
    public function eligibleDownlines(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isAffiliator(), 403, 'Akses terbatas untuk akun Mitra Affiliator.');

        return response()->json([
            'downlines'     => $this->commissionClaimService->getEligibleDownlinesForCommission($user),
            'exchange_rate' => $this->commissionClaimService->getExchangeRateInfo(),
        ]);
    }
}
