<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyReferralClaimRequest;
use App\Models\KlaimReferral;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminReferralClaimController extends Controller
{
    public function __construct(
        private readonly ReferralService $referralService,
    ) {}

    /**
     * Tampilkan daftar verifikasi permohonan klaim susulan referral.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'sort_by', 'sort_direction', 'per_page']);

        return Inertia::render('Admin/ReferralClaims/Index', [
            'claims'       => $this->referralService->getPaginatedClaims($filters),
            'filters'      => $filters,
            'pendingCount' => KlaimReferral::where('status', 'pending')->count(),
        ]);
    }

    /**
     * Ekspor data permohonan klaim referral susulan ke format XLSX.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'status', 'sort_by', 'sort_direction', 'claim_search', 'claim_status', 'claim_sort_by', 'claim_sort_direction']);

        return $this->referralService->exportClaimsXlsx($filters);
    }

    /**
     * Proses keputusan persetujuan atau penolakan permohonan klaim susulan.
     */
    public function verify(VerifyReferralClaimRequest $request, int $id): RedirectResponse
    {
        /** @var KlaimReferral $claim */
        $claim = KlaimReferral::findOrFail($id);

        $this->referralService->reviewClaim(
            $claim,
            $request->string('status')->value(),
            $request->input('catatan_admin'),
            $request->user()
        );

        $actionLabel = $request->input('status') === 'approved' ? 'disetujui' : 'ditolak';

        $referer = (string) $request->headers->get('referer', '');
        if ($request->boolean('from_affiliates') || str_contains($referer, '/admin/affiliates')) {
            return redirect()->route('admin.affiliates.index', ['tab' => 'claims'])
                ->with('success', "Permohonan klaim referral berhasil {$actionLabel}.");
        }

        return redirect()->route('admin.referral_claims.index')
            ->with('success', "Permohonan klaim referral berhasil {$actionLabel}.");
    }
}
