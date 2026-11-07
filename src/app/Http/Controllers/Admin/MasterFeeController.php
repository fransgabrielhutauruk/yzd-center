<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectMasterFeeRequest;
use App\Http\Requests\Admin\UpdatePengaturanMasterFeeRequest;
use App\Services\MasterFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterFeeController extends Controller
{
    public function __construct(
        protected MasterFeeService $masterFeeService
    ) {}

    /**
     * Tampilkan halaman kelola tagihan master fee untuk admin.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['periode', 'periode_dari', 'periode_sampai', 'status', 'search', 'terlambat', 'tab', 'missing_periode']);
        $currentTab = $filters['tab'] ?? 'tagihan';

        $tagihans = $this->masterFeeService->paginateForAdmin($filters);
        $missingTarget = $filters['missing_periode'] ?? $filters['periode_sampai'] ?? $filters['periode'] ?? null;
        $missing = $this->masterFeeService->missingRecap($missingTarget, $filters);
        $summary = $this->masterFeeService->summary($filters);
        $settings = $this->masterFeeService->getSettings();

        return Inertia::render('Admin/MasterFee/Index', [
            'tagihans'    => $tagihans,
            'missing'     => $missing,
            'summary'     => $summary,
            'settings'    => $settings,
            'filters'     => $filters,
            'current_tab' => $currentTab,
        ]);
    }

    /**
     * Verifikasi tagihan master fee (menjadi LUNAS).
     */
    public function verify(Request $request, int $id): RedirectResponse
    {
        $tagihan = $this->masterFeeService->verify($id, $request->user());

        return redirect()->back()->with('success', "Tagihan {$tagihan->nomor} berhasil diverifikasi dan berstatus LUNAS.");
    }

    /**
     * Tolak bukti transfer tagihan master fee.
     */
    public function reject(RejectMasterFeeRequest $request, int $id): RedirectResponse
    {
        $tagihan = $this->masterFeeService->reject($id, $request->user(), $request->input('alasan'));

        return redirect()->back()->with('success', "Bukti transfer tagihan {$tagihan->nomor} telah ditolak.");
    }

    /**
     * Tampilkan halaman pengaturan rekening & persen Master Fee.
     */
    public function settings(Request $request): Response
    {
        $settings = $this->masterFeeService->getSettings();

        return Inertia::render('Admin/Settings/MasterFee', [
            'settings' => $settings,
        ]);
    }

    /**
     * Perbarui pengaturan Master Fee.
     */
    public function updateSettings(UpdatePengaturanMasterFeeRequest $request): RedirectResponse
    {
        $this->masterFeeService->updateSettings($request->validated(), $request->user());

        return redirect()->back()->with('success', 'Pengaturan Master Fee berhasil diperbarui.');
    }
}
