<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlagCorrectionRequest;
use App\Http\Requests\Admin\RejectMasterFeeRequest;
use App\Http\Requests\Admin\UpdatePengaturanMasterFeeRequest;
use App\Models\LaporanProfitBulanan;
use App\Services\MasterFeeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MasterFeeController extends Controller
{
    public function __construct(
        protected MasterFeeService $masterFeeService
    ) {}

    /**
     * Tampilkan halaman kelola laporan profit dan master fee untuk admin.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['periode', 'status', 'search', 'perlu_koreksi', 'tab', 'missing_page', 'laporan_page']);
        $currentTab = $filters['tab'] ?? 'laporan';

        $paginated = $this->masterFeeService->paginateForAdmin($filters);
        $missing = $this->masterFeeService->missingReports($filters['periode'] ?? null, $filters);
        $summary = $this->masterFeeService->summary($filters);
        $settings = $this->masterFeeService->getSettings();

        $laporansFormatted = $paginated->through(function (LaporanProfitBulanan $l) {
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
                'user_id'              => $l->user_id,
                'user_name'            => $l->user?->name ?? '-',
                'user_username'        => $l->user?->username ?? '-',
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
                'verifier_name'        => $l->verifier?->name,
                'verified_at'          => $l->verified_at ? tanggal_waktu_indo($l->verified_at) : null,
                'urls'                 => [
                    'bukti_myfxbook' => $l->bukti_myfxbook_path ? route('documents.master_fee_myfxbook', $l->id) : null,
                    'bukti_wd'       => $l->bukti_wd_path ? route('documents.master_fee_wd', $l->id) : null,
                    'bukti_transfer' => $l->bukti_transfer_path ? route('documents.master_fee_proof', $l->id) : null,
                ],
            ];
        });

        return Inertia::render('Admin/MasterFee/Index', [
            'laporans'    => $laporansFormatted,
            'tagihans'    => $laporansFormatted, // backward compatibility
            'missing'     => $missing,
            'summary'     => $summary,
            'settings'    => $settings,
            'filters'     => $filters,
            'current_tab' => $currentTab,
        ]);
    }

    /**
     * Ekspor data master fee sesuai tab aktif (laporan profit / akun belum lapor) ke format XLSX.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $tab = $request->query('tab', 'laporan');
        $filters = $request->only(['periode', 'status', 'search', 'perlu_koreksi']);

        if ($tab === 'belum_lapor') {
            return $this->masterFeeService->exportMissingReportsXlsx($filters);
        }

        return $this->masterFeeService->exportReportsXlsx($filters);
    }

    /**
     * Verifikasi laporan master fee (menjadi LUNAS).
     */
    public function verify(Request $request, int $id): RedirectResponse
    {
        $laporan = $this->masterFeeService->verify($id, $request->user());

        return redirect()->back()->with('success', "Laporan Master Fee {$laporan->nomor} berhasil diverifikasi dan berstatus LUNAS.");
    }

    /**
     * Tolak bukti transfer master fee.
     */
    public function reject(RejectMasterFeeRequest $request, int $id): RedirectResponse
    {
        $laporan = $this->masterFeeService->reject($id, $request->user(), $request->input('alasan'));

        return redirect()->back()->with('success', "Bukti transfer Master Fee {$laporan->nomor} telah ditolak.");
    }

    /**
     * Tandai laporan perlu koreksi oleh admin.
     */
    public function flagCorrection(FlagCorrectionRequest $request, int $id): RedirectResponse
    {
        $laporan = $this->masterFeeService->flagCorrection($id, $request->user(), $request->input('alasan'));

        return redirect()->back()->with('success', "Laporan profit {$laporan->nomor} berhasil ditandai Perlu Koreksi.");
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
