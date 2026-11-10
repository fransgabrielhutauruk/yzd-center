<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreLaporanProfitRequest;
use App\Http\Requests\Member\UpdateLaporanProfitRequest;
use App\Http\Requests\Member\UploadBuktiMasterFeeRequest;
use App\Models\AkunCopytrade;
use App\Models\LaporanProfitBulanan;
use App\Services\MasterFeeService;
use Carbon\Carbon;
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
     * Tampilkan halaman daftar laporan profit dan master fee milik member.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['status', 'periode', 'search']);

        $paginated = $this->masterFeeService->paginateForMember($user, $filters);
        $settings = $this->masterFeeService->getSettings();
        $availableAccounts = $this->masterFeeService->availableReportableAccounts($user);

        $totalMenungguPembayaran = (float) LaporanProfitBulanan::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                LaporanProfitBulanan::STATUS_BELUM_DIBAYAR,
                LaporanProfitBulanan::STATUS_DITOLAK,
            ])
            ->sum('fee_usd');

        $totalMenungguVerifikasi = (float) LaporanProfitBulanan::query()
            ->where('user_id', $user->id)
            ->where('status', LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI)
            ->sum('fee_usd');

        $totalLunas = (float) LaporanProfitBulanan::query()
            ->where('user_id', $user->id)
            ->where('status', LaporanProfitBulanan::STATUS_LUNAS)
            ->sum('fee_usd');

        // Transform collection to add formatted fields and URLs
        $reportsFormatted = $paginated->through(function (LaporanProfitBulanan $l) {
            $pAwal = Carbon::parse($l->periode_awal);
            $pAkhir = Carbon::parse($l->periode_akhir);

            return [
                'id'                   => $l->id,
                'nomor'                => $l->nomor,
                'akun_copytrade_id'    => $l->akun_copytrade_id,
                'nomor_akun_trading'   => $l->akun?->nomor_akun_trading ?? '-',
                'broker'               => $l->akun?->broker ?? '-',
                'tier'                 => $l->akun?->tier ?? 'tier_1',
                'modal_riil'           => (float) ($l->akun?->modal_riil ?? 0),
                'myfxbook_url'         => $l->akun?->myfxbook_url,
                'periode_ke'           => $l->periode_ke,
                'periode_awal'         => $l->periode_awal->toDateString(),
                'periode_akhir'        => $l->periode_akhir->toDateString(),
                'periode_label'        => $pAwal->translatedFormat('d M Y') . ' - ' . $pAkhir->translatedFormat('d M Y'),
                'jatuh_tempo'          => $l->jatuh_tempo->toDateString(),
                'jatuh_tempo_label'    => tanggal_indo($l->jatuh_tempo),
                'profit_usd'           => (float) $l->profit_usd,
                'profit_usd_formatted' => '$' . number_format((float) $l->profit_usd, 2),
                'wd_usd'               => $l->wd_usd !== null ? (float) $l->wd_usd : null,
                'wd_idr'               => $l->wd_idr,
                'kurs_riil'            => $l->kurs_riil !== null ? (float) $l->kurs_riil : null,
                'fee_persen'           => (float) $l->fee_persen,
                'fee_usd'              => (float) $l->fee_usd,
                'fee_usd_formatted'    => '$' . number_format((float) $l->fee_usd, 2),
                'fee_idr'              => (int) $l->fee_idr,
                'fee_idr_formatted'    => 'Rp ' . number_format((int) $l->fee_idr, 0, ',', '.'),
                'status'               => $l->status,
                'status_tampil'        => $l->status_tampil,
                'status_catatan'       => $l->status_catatan,
                'perlu_koreksi'        => (bool) $l->perlu_koreksi,
                'catatan_koreksi'      => $l->catatan_koreksi,
                'catatan_admin'        => $l->catatan_admin,
                'dibayar_at'           => $l->dibayar_at ? tanggal_waktu_indo($l->dibayar_at) : null,
                'is_editable'          => $l->is_editable,
                'is_terkunci'          => $l->is_terkunci,
                'urls'                 => [
                    'bukti_myfxbook' => $l->bukti_myfxbook_path ? route('documents.master_fee_myfxbook', $l->id) : null,
                    'bukti_wd'       => $l->bukti_wd_path ? route('documents.master_fee_wd', $l->id) : null,
                    'bukti_transfer' => $l->bukti_transfer_path ? route('documents.master_fee_proof', $l->id) : null,
                ],
            ];
        });

        $availableAccountsFormatted = array_map(function ($item) {
            $akun = $item['akun'];
            $next = $item['next_period'];

            return [
                'akun_id'            => $akun->id,
                'nomor_akun_trading' => $akun->nomor_akun_trading,
                'broker'             => $akun->broker,
                'modal_riil'         => (float) $akun->modal_riil,
                'myfxbook_url'       => $akun->myfxbook_url,
                'periode_ke'         => $next['ke'],
                'periode_awal'       => $next['awal']->toDateString(),
                'periode_akhir'      => $next['akhir']->toDateString(),
                'periode_label'      => $next['awal']->translatedFormat('d M Y') . ' - ' . $next['akhir']->translatedFormat('d M Y'),
                'open_date'          => $next['open_date']->toDateString(),
            ];
        }, $availableAccounts);

        return Inertia::render('Member/MasterFee/Index', [
            'laporans'                  => $reportsFormatted,
            'filters'                   => $filters,
            'settings'                  => $settings,
            'available_accounts'        => $availableAccountsFormatted,
            'total_menunggu_pembayaran' => round($totalMenungguPembayaran, 2),
            'total_menunggu_verifikasi' => round($totalMenungguVerifikasi, 2),
            'total_lunas'               => round($totalLunas, 2),
            'total_belum_dibayar'       => round($totalMenungguPembayaran + $totalMenungguVerifikasi, 2),
        ]);
    }

    /**
     * Simpan laporan profit bulanan baru.
     */
    public function store(StoreLaporanProfitRequest $request): RedirectResponse
    {
        $user = $request->user();
        $akun = AkunCopytrade::where('user_id', $user->id)
            ->findOrFail($request->input('akun_copytrade_id'));

        $laporan = $this->masterFeeService->submitReport(
            $akun,
            $request->validated(),
            $request->file('bukti_myfxbook'),
            $request->file('bukti_wd'),
            $user
        );

        $msg = "Laporan profit periode ke-{$laporan->periode_ke} (#{$akun->nomor_akun_trading}) berhasil dikirim.";
        if ($laporan->fee_usd > 0) {
            $msg .= ' Silakan lakukan transfer Master Fee dan unggah bukti pembayaran.';
        }

        return redirect()->route('member.master_fee.index')->with('success', $msg);
    }

    /**
     * Perbarui laporan profit yang editable / perlu koreksi.
     */
    public function update(UpdateLaporanProfitRequest $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $laporan = LaporanProfitBulanan::where('user_id', $user->id)->findOrFail($id);

        $this->masterFeeService->updateReport(
            $laporan,
            $request->validated(),
            $request->file('bukti_myfxbook'),
            $request->file('bukti_wd'),
            $user
        );

        return redirect()->back()->with('success', "Laporan profit {$laporan->nomor} berhasil diperbarui.");
    }

    /**
     * Hapus laporan profit (hanya jika editable dan laporan paling akhir).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $laporan = LaporanProfitBulanan::where('user_id', $user->id)->findOrFail($id);

        $this->masterFeeService->deleteReport($laporan, $user);

        return redirect()->back()->with('success', 'Laporan profit berhasil dihapus.');
    }

    /**
     * Mengambil detail JSON laporan profit untuk modal rincian.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $laporan = LaporanProfitBulanan::with(['akun', 'verifier'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $settings = $this->masterFeeService->getSettings();

        return response()->json([
            'laporan'  => $laporan,
            'settings' => $settings,
            'urls'     => [
                'bukti_myfxbook' => $laporan->bukti_myfxbook_path ? route('documents.master_fee_myfxbook', $laporan->id) : null,
                'bukti_wd'       => $laporan->bukti_wd_path ? route('documents.master_fee_wd', $laporan->id) : null,
                'bukti_transfer' => $laporan->bukti_transfer_path ? route('documents.master_fee_proof', $laporan->id) : null,
            ],
        ]);
    }

    /**
     * Unggah bukti transfer pembayaran master fee.
     */
    public function uploadProof(UploadBuktiMasterFeeRequest $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $laporan = LaporanProfitBulanan::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->masterFeeService->submitProof($laporan, $request->file('bukti_transfer'), $user);

        return redirect()->back()->with('success', "Bukti transfer untuk Master Fee {$laporan->nomor} berhasil diunggah dan sedang menunggu verifikasi admin.");
    }
}
