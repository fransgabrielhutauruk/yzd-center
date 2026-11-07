<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessCommissionClaimRequest;
use App\Services\CommissionClaimService;
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
     * Tampilkan antrean verifikasi dan pencairan klaim komisi affiliator.
     */
    public function index(Request $request): Response
    {
        $filters = array_filter([
            'search'         => $request->query('search'),
            'status'         => $request->query('status'),
            'sort_by'        => $request->query('sort_by'),
            'sort_direction' => $request->query('sort_direction'),
            'per_page'       => $request->query('per_page'),
        ], fn ($val) => filled($val));

        $stats = $this->commissionClaimService->getAdminCommissionStats();

        return Inertia::render('Admin/Commissions/Index', [
            'claims'  => $this->commissionClaimService->getPaginatedClaims($filters),
            'filters' => $filters,
            'counts'  => $stats,
            'stats'   => $stats,
        ]);
    }

    /**
     * Eksekusi keputusan persetujuan pencairan transfer atau penolakan klaim komisi.
     */
    public function process(ProcessCommissionClaimRequest $request, int $id): RedirectResponse
    {
        $status = $request->string('status')->value();
        $kursKonversiIdr = $request->filled('kurs_konversi_idr') ? (float) $request->input('kurs_konversi_idr') : null;
        $buktiTransfer = $request->file('bukti_transfer');
        $catatanAdmin = $request->input('catatan_admin');
        $itemizedProfits = $request->input('items');
        $profitMaster = $request->filled('profit_master') ? (float) $request->input('profit_master') : null;

        $claim = $this->commissionClaimService->processClaim(
            $id,
            $status,
            $profitMaster,
            $buktiTransfer,
            $catatanAdmin,
            $request->user(),
            $itemizedProfits,
            $kursKonversiIdr
        );

        $actionLabel = $status === 'approved_transferred'
            ? 'disetujui & berhasil dicairkan'
            : 'ditolak';

        return redirect()->route('admin.commissions.index')
            ->with('success', "Pengajuan klaim komisi ({$claim->kode_klaim}) milik {$claim->affiliator->name} berhasil {$actionLabel}.");
    }
}
