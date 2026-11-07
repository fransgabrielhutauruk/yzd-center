<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UploadBuktiMasterFeeRequest;
use App\Models\TagihanMasterFee;
use App\Services\MasterFeeService;
use Illuminate\Http\JsonResponse;
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
     * Tampilkan halaman daftar tagihan master fee milik member.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['status', 'periode', 'periode_dari', 'periode_sampai', 'search']);

        $tagihans = $this->masterFeeService->paginateForMember($user, $filters);
        $settings = $this->masterFeeService->getSettings();

        $totalBelumDibayar = (float) TagihanMasterFee::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [TagihanMasterFee::STATUS_BELUM_DIBAYAR, TagihanMasterFee::STATUS_DITOLAK])
            ->sum('nominal_fee');

        $totalLunas = (float) TagihanMasterFee::query()
            ->where('user_id', $user->id)
            ->where('status', TagihanMasterFee::STATUS_LUNAS)
            ->sum('nominal_fee');

        return Inertia::render('Member/MasterFee/Index', [
            'tagihans'            => $tagihans,
            'filters'             => $filters,
            'settings'            => $settings,
            'total_belum_dibayar' => round($totalBelumDibayar, 2),
            'total_lunas'         => round($totalLunas, 2),
        ]);
    }

    /**
     * Mengambil detail JSON tagihan untuk modal rincian.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $tagihan = TagihanMasterFee::with(['akun', 'riwayatBalance', 'verifier'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $settings = $this->masterFeeService->getSettings();

        return response()->json([
            'tagihan'  => $tagihan,
            'settings' => $settings,
            'urls'     => [
                'bukti_transfer' => $tagihan->bukti_transfer_path ? route('documents.master_fee_proof', $tagihan->id) : null,
                'bukti_saldo'    => $tagihan->riwayatBalance?->bukti_saldo_path ? route('documents.balance_proof', $tagihan->riwayat_balance_id) : null,
            ],
        ]);
    }

    /**
     * Unggah bukti transfer pembayaran master fee.
     */
    public function uploadProof(UploadBuktiMasterFeeRequest $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $tagihan = TagihanMasterFee::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->masterFeeService->submitProof($tagihan, $request->file('bukti_transfer'), $user);

        return redirect()->back()->with('success', "Bukti transfer untuk tagihan {$tagihan->nomor} berhasil diunggah dan sedang menunggu verifikasi admin.");
    }
}
