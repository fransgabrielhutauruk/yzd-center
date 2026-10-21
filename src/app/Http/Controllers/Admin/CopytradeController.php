<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyCopytradeRequest;
use App\Models\AkunCopytrade;
use App\Services\AuditLogService;
use App\Services\CopytradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CopytradeController extends Controller
{
    public function __construct(
        private readonly CopytradeService $copytradeService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Kelola Copytrade: tab Verifikasi (pending/ditolak) dan tab Akun Aktif (pemantauan).
     */
    public function index(Request $request): Response
    {
        $ver = $this->tableParams($request, 'ver_', 'created_at', 'desc');
        $ver['status'] = trim((string) $request->query('ver_status', ''));
        $ver['tier'] = trim((string) $request->query('ver_tier', ''));

        $akt = $this->tableParams($request, 'akt_', 'tanggal_mulai', 'desc');
        $akt['tier'] = trim((string) $request->query('akt_tier', ''));
        $akt['catatan'] = trim((string) $request->query('akt_catatan', ''));
        $akt['username'] = trim((string) ($request->query('akt_username') ?? $request->query('username') ?? ''));

        return Inertia::render('Admin/Copytrade/Index', [
            'activeTab'  => $request->query('tab') === 'aktif' ? 'aktif' : 'verifikasi',
            'counts'     => $this->copytradeService->getAdminCounts(),
            'verifikasi' => $this->copytradeService->paginateVerificationQueue($ver),
            'aktif'      => $this->copytradeService->paginateActiveAccounts($akt),
            'verFilters' => [
                'ver_search'         => $ver['search'],
                'ver_sort_by'        => $ver['sort_by'],
                'ver_sort_direction' => $ver['sort_direction'],
                'ver_per_page'       => $ver['per_page'],
                'ver_status'         => $ver['status'],
                'ver_tier'           => $ver['tier'],
            ],
            'aktFilters' => [
                'akt_search'         => $akt['search'],
                'akt_sort_by'        => $akt['sort_by'],
                'akt_sort_direction' => $akt['sort_direction'],
                'akt_per_page'       => $akt['per_page'],
                'akt_tier'           => $akt['tier'],
                'akt_catatan'        => $akt['catatan'],
                'akt_username'       => $akt['username'] ?: null,
            ],
        ]);
    }

    /**
     * Detail akun (JSON) untuk modal: kredensial tier 3 dan riwayat saldo. Tidak boleh di-cache.
     */
    public function detail(Request $request, int $id): JsonResponse
    {
        $akun = AkunCopytrade::with('user')->findOrFail($id);
        $user = $request->user();

        // Audit Log: Akses kredensial MT4/VPS Tier 3 oleh Admin
        if ($user && $user->isAdmin() && $akun->tier === 'tier_3') {
            $this->auditLogService->record(
                kategori: 'copytrade',
                aksi: 'lihat_kredensial_mt4',
                deskripsi: "Administrator {$user->name} melihat kredensial MT4/VPS untuk akun Tier 3 #{$akun->nomor_akun_trading}.",
                subject: $akun,
                tingkat: 'warning',
                user: $user,
                withCooldown: true
            );
        }

        return response()
            ->json($this->copytradeService->getAccountDetail($akun))
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Verifikasi keputusan permohonan copy trade.
     */
    public function verify(VerifyCopytradeRequest $request, int $id): RedirectResponse
    {
        $akun = AkunCopytrade::with('user')->findOrFail($id);
        $validated = $request->validated();

        $this->copytradeService->verifyAccount(
            $akun,
            $validated['status'],
            $validated['catatan_admin'] ?? null,
            $request->user()
        );

        $statusText = $validated['status'] === 'active' ? 'disetujui & diaktifkan' : 'ditolak';

        return redirect()->back()->with('success', "Permohonan akun copytrade ({$akun->nomor_akun_trading}) berhasil {$statusText}.");
    }

    /**
     * Parameter tabel standar (search, sort, per_page, nama halaman) untuk satu tab berprefix.
     */
    private function tableParams(Request $request, string $prefix, string $defaultSort, string $defaultDirection): array
    {
        $perPage = (int) $request->query($prefix . 'per_page', 10);

        return [
            'search'         => trim((string) $request->query($prefix . 'search', '')),
            'sort_by'        => (string) $request->query($prefix . 'sort_by', $defaultSort),
            'sort_direction' => (string) $request->query($prefix . 'sort_direction', $defaultDirection),
            'per_page'       => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10,
            'page_name'      => $prefix . 'page',
        ];
    }
}
