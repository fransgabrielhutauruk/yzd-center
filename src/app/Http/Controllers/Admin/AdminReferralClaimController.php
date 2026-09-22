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

        return redirect()->route('admin.referral_claims.index')
            ->with('success', "Permohonan klaim referral berhasil {$actionLabel}.");
    }
}
